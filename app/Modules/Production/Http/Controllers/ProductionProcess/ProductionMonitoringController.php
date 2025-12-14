<?php

namespace App\Modules\Production\Http\Controllers\ProductionProcess;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\ProductionProcess\WorkOrder;
use App\Modules\Production\Models\PdMasterData\Setting;
use App\Modules\Production\Models\PdMasterData\Machine;
use App\Modules\Production\Models\PdMasterData\Shift;
use App\Modules\MasterData\Models\MUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductionMonitoringController extends Controller
{
  public function index()
  {
    // Get active work orders
    $workOrders = WorkOrder::where('is_delete', 'N')
      ->whereIn('wo_status', ['Release', 'On Process'])
      ->orderBy('wo_no', 'desc')
      ->get();

    // Get all machines
    $machines = Machine::all();

    // Get shifts
    $shifts = Shift::all();

    // Get users filtered by role
    $supervisors = MUser::where('role', 'supervisor')->orderBy('name', 'asc')->get();
    $operators = MUser::where('role', 'operator')->orderBy('name', 'asc')->get();

    return view('Production::production-process.production-monitoring.index', compact('workOrders', 'machines', 'shifts', 'supervisors', 'operators'));
  }

  // AJAX: Get WO Details
  public function getWoDetails(Request $request)
  {
    $woNo = $request->wo_no;

    $wo = WorkOrder::with(['routing.details'])
      ->where('wo_no', $woNo)
      ->where('is_delete', 'N')
      ->first();

    if (!$wo) {
      return response()->json(['error' => 'Work Order not found'], 404);
    }

    return response()->json([
      'wo_qty' => $wo->wo_qty,
      'part_no' => $wo->part_no,
      'part_name' => $wo->part_name,
      'routing_id' => $wo->routing ? $wo->routing->routing_id : null,
      'routing_name' => $wo->routing ? $wo->routing->routing_name : null
    ]);
  }

  // AJAX: Get Process List by WO
  public function getProcessList(Request $request)
  {
    $woNo = $request->wo_no;

    $wo = WorkOrder::with(['routing.details.masterProcess'])
      ->where('wo_no', $woNo)
      ->where('is_delete', 'N')
      ->first();

    if (!$wo || !$wo->routing) {
      return response()->json(['error' => 'Routing not found for this Work Order'], 404);
    }

    $processes = $wo->routing->details->map(function ($detail) {
      return [
        'process_id' => $detail->process_id,
        'process_name' => $detail->process_name,
        'process_desc' => $detail->process_desc,
        'cycle_time' => $detail->cycle_time_second,
        'urutan_proses' => $detail->urutan_proses
      ];
    });

    return response()->json(['processes' => $processes]);
  }

  // AJAX: Get Cycle Time by Process
  public function getCycleTime(Request $request)
  {
    $woNo = $request->wo_no;
    $processId = $request->process_id;

    $wo = WorkOrder::with(['routing.details'])
      ->where('wo_no', $woNo)
      ->where('is_delete', 'N')
      ->first();

    if (!$wo || !$wo->routing) {
      return response()->json(['error' => 'Routing not found'], 404);
    }

    $processDetail = $wo->routing->details->where('process_id', $processId)->first();

    if (!$processDetail) {
      return response()->json(['error' => 'Process not found in routing'], 404);
    }

    return response()->json([
      'cycle_time' => $processDetail->cycle_time_second
    ]);
  }

  public function startProduction(Request $request)
  {
    $request->validate([
      'wo_no' => 'required|exists:t_wo,wo_no',
      'process_id' => 'required',
      'supervisor' => 'required|string',
      'operator' => 'required|string',
      'machine_id' => 'required|exists:m_machine,id',
      'shift_id' => 'required|exists:m_shifts,shift_id',
    ]);

    $wo = WorkOrder::where('wo_no', $request->wo_no)->first();

    // Get process details
    $processDetail = $wo->routing->details->where('process_id', $request->process_id)->first();

    // Create production monitoring record
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::create([
      'wo_no' => $request->wo_no,
      'wo_qty' => $wo->wo_qty,
      'process_id' => $request->process_id,
      'process_name' => $processDetail->process_name,
      'cycle_time' => $processDetail->cycle_time_second,
      'supervisor' => $request->supervisor,
      'operator' => $request->operator,
      'machine_id' => $request->machine_id,
      'shift_id' => $request->shift_id,
      'start_time' => now(),
      'current_status' => 'Ready',
      'qty_ok' => 0,
      'qty_ng' => 0,
      'qty_actual' => 0,
      'is_active' => 1,
      'created_by' => auth()->check() ? auth()->user()->name : 'system',
      'created_at' => now()
    ]);

    // Create initial status log
    \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::create([
      'monitoring_id' => $monitoring->monitoring_id,
      'status' => 'Ready',
      'start_time' => now(),
      'created_at' => now()
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Production started successfully',
      'monitoring_id' => $monitoring->monitoring_id,
      'redirect_url' => route('production.production-monitoring.tv-display', $monitoring->monitoring_id)
    ]);
  }

  public function dashboard($id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::with([
      'workOrder',
      'machine',
      'shift',
      'statusLogs' => function ($q) {
        $q->orderBy('start_time', 'desc');
      },
      'downtimeLogs',
      'ngLogs'
    ])->findOrFail($id);

    return view('Production::production-process.production-monitoring.dashboard', compact('monitoring'));
  }

  public function tvDisplay($id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::with([
      'workOrder.lot',
      'machine',
      'shift',
      'statusLogs',
      'downtimeLogs',
      'ngLogs'
    ])->findOrFail($id);

    return view('Production::production-process.production-monitoring.tv-display', compact('monitoring'));
  }

  public function getTvData($id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::with([
      'statusLogs' => function ($q) {
        $q->orderBy('start_time', 'asc');
      }
    ])->findOrFail($id);

    // Calculate OEE metrics
    $quality = $monitoring->qty_actual > 0 ? ($monitoring->qty_ok / $monitoring->qty_actual * 100) : 0;

    // Calculate availability (time running / total time)
    $totalTime = now()->diffInSeconds($monitoring->start_time);
    $runningTime = $monitoring->statusLogs->where('status', 'Running')->sum('duration_seconds');
    $availability = $totalTime > 0 ? ($runningTime / $totalTime * 100) : 0;

    // Calculate performance (actual output / expected output)
    $expectedOutput = $monitoring->cycle_time > 0 ? ($runningTime / $monitoring->cycle_time) : 0;
    $performance = $expectedOutput > 0 ? ($monitoring->qty_actual / $expectedOutput * 100) : 0;

    // OEE = Availability × Performance × Quality
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    // Calculate cycle times (mock data for now - you can enhance this)
    $avgCycleTime = $monitoring->cycle_time;
    $lastCycleTime = $monitoring->cycle_time;
    $highCycleTime = $monitoring->cycle_time * 1.2;
    $lowCycleTime = $monitoring->cycle_time * 0.8;

    // Prepare timeline data
    $timeline = $monitoring->statusLogs->map(function ($log) {
      return [
        'time' => $log->start_time->format('H:i'),
        'status' => $log->status,
        'duration' => $log->duration_seconds ?? 0
      ];
    });

    // Check for MQTT signals
    $showDowntimeForm = Cache::pull("mqtt_show_downtime_form_{$id}", false);
    $showNgForm = Cache::pull("mqtt_show_ng_form_{$id}", false);

    return response()->json([
      'wo_qty' => $monitoring->wo_qty,
      'qty_actual' => $monitoring->qty_actual,
      'qty_ng' => $monitoring->qty_ng,
      'qty_ok' => $monitoring->qty_ok,
      'current_status' => $monitoring->current_status,
      'oee' => number_format($oee, 1),
      'availability' => number_format($availability, 1),
      'performance' => number_format($performance, 1),
      'quality' => number_format($quality, 1),
      'avg_cycle_time' => number_format($avgCycleTime, 1),
      'last_cycle_time' => number_format($lastCycleTime, 1),
      'high_cycle_time' => number_format($highCycleTime, 1),
      'low_cycle_time' => number_format($lowCycleTime, 1),
      'timeline' => $timeline,
      'mqtt_signals' => [
        'show_downtime_form' => $showDowntimeForm,
        'show_ng_form' => $showNgForm
      ]
    ]);
  }

  public function updateStatus(Request $request, $id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    $newStatus = $request->status;

    // Close previous status log
    $lastLog = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
      ->whereNull('end_time')
      ->latest('start_time')
      ->first();

    if ($lastLog) {
      $lastLog->update([
        'end_time' => now(),
        'duration_seconds' => now()->diffInSeconds($lastLog->start_time)
      ]);
    }

    // Create new status log
    \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::create([
      'monitoring_id' => $id,
      'status' => $newStatus,
      'start_time' => now(),
      'created_at' => now()
    ]);

    // Update monitoring status
    $monitoring->update([
      'current_status' => $newStatus,
      'updated_at' => now()
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Status updated successfully'
    ]);
  }

  public function updateQtyOk(Request $request, $id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    $monitoring->increment('qty_ok', $request->qty ?? 1);
    $monitoring->increment('qty_actual', $request->qty ?? 1);

    return response()->json([
      'success' => true,
      'qty_ok' => $monitoring->qty_ok,
      'qty_actual' => $monitoring->qty_actual
    ]);
  }

  public function saveDowntime(Request $request, $id)
  {
    $request->validate([
      'downtime_type' => 'required|string',
      'downtime_reason' => 'required|string',
    ]);

    \App\Modules\Production\Models\ProductionProcess\ProductionDowntime::create([
      'monitoring_id' => $id,
      'downtime_type' => $request->downtime_type,
      'downtime_reason' => $request->downtime_reason,
      'start_time' => now(),
      'notes' => $request->notes,
      'created_at' => now()
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Downtime recorded successfully'
    ]);
  }

  public function saveNg(Request $request, $id)
  {
    $request->validate([
      'ng_type' => 'required|string',
      'ng_reason' => 'required|string',
      'qty' => 'required|integer|min:1',
    ]);

    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    \App\Modules\Production\Models\ProductionProcess\ProductionNg::create([
      'monitoring_id' => $id,
      'ng_type' => $request->ng_type,
      'ng_reason' => $request->ng_reason,
      'qty' => $request->qty,
      'notes' => $request->notes,
      'created_at' => now()
    ]);

    $monitoring->increment('qty_ng', $request->qty);
    $monitoring->increment('qty_actual', $request->qty);

    return response()->json([
      'success' => true,
      'message' => 'NG recorded successfully',
      'qty_ng' => $monitoring->qty_ng,
      'qty_actual' => $monitoring->qty_actual
    ]);
  }

  public function checkMqttNgSignal($id)
  {
    $signal = Cache::pull("mqtt_ng_signal_{$id}");

    if ($signal) {
      return response()->json([
        'show' => $signal['show'] ?? false,
        'qty' => $signal['qty'] ?? 1
      ]);
    }

    return response()->json([
      'show' => false,
      'qty' => 1
    ]);
  }

  public function checkMqttDowntimeSignal($id)
  {
    $signal = Cache::pull("mqtt_show_downtime_form_{$id}");

    if ($signal) {
      return response()->json([
        'show' => true
      ]);
    }

    return response()->json([
      'show' => false
    ]);
  }

  public function checkMqttStatusSignal($id)
  {
    $signal = Cache::pull("mqtt_status_signal_{$id}");

    if ($signal) {
      return response()->json([
        'show' => true,
        'status' => $signal['status'] ?? null
      ]);
    }

    return response()->json([
      'show' => false,
      'status' => null
    ]);
  }
}
