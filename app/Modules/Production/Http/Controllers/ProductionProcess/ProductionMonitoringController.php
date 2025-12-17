<?php

namespace App\Modules\Production\Http\Controllers\ProductionProcess;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\ProductionProcess\WorkOrder;
use App\Modules\Production\Models\PdMasterData\Setting;
use App\Modules\Production\Models\PdMasterData\Machine;
use App\Modules\Production\Models\PdMasterData\Shift;
use App\Modules\MasterData\Models\MUser;
use App\Modules\Production\Services\OeeCalculationService;
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

    // Get current time in Indonesia timezone (WIB - UTC+7)
    $nowIndonesia = now('Asia/Jakarta');

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
      'start_time' => $nowIndonesia,
      'current_status' => 'Ready',
      'qty_ok' => 0,
      'qty_ng' => 0,
      'qty_actual' => 0,
      'is_active' => 1,
      'created_by' => auth()->check() ? auth()->user()->name : 'system',
      'created_at' => $nowIndonesia
    ]);

    // Create initial status log
    \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::create([
      'monitoring_id' => $monitoring->monitoring_id,
      'status' => 'Ready',
      'start_time' => $nowIndonesia,
      'created_at' => $nowIndonesia
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

    // Get realtime OEE metrics from service
    $metrics = OeeCalculationService::getRealtimeMetrics($id);

    // Log metrics for debugging
    \Log::info("OEE Metrics for monitoring {$id}", $metrics);

    // Prepare timeline data
    $timeline = $monitoring->statusLogs->map(function ($log) {
      $startTime = $log->start_time->format('H:i:s');
      $endTime = $log->end_time ? $log->end_time->format('H:i:s') : null;
      $duration = $log->duration_seconds ?? 0;

      // Debug: Log raw data from database
      \Log::info("Status Log", [
        'status' => $log->status,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'duration_seconds' => $duration
      ]);

      return [
        'start_time' => $startTime,
        'end_time' => $endTime,
        'status' => $log->status,
        'duration' => $duration
      ];
    });

    // Log timeline for debugging
    \Log::info("Timeline data for monitoring {$id}", [
      'timeline' => $timeline->toArray()
    ]);

    return response()->json([
      'wo_qty' => $monitoring->wo_qty,
      'qty_actual' => $monitoring->qty_actual,
      'qty_ng' => $monitoring->qty_ng,
      'qty_ok' => $monitoring->qty_ok,
      'current_status' => $monitoring->current_status,
      'oee' => $metrics['oee'],
      'availability' => $metrics['availability'],
      'performance' => $metrics['performance'],
      'quality' => $metrics['quality'],
      'uptime' => $metrics['uptime'],
      'avg_cycle_time' => $metrics['avg_cycle_time'],
      'last_cycle_time' => $metrics['last_cycle_time'],
      'high_cycle_time' => $metrics['high_cycle_time'],
      'low_cycle_time' => $metrics['low_cycle_time'],
      'timeline' => $timeline
    ]);
  }

  public function updateStatus(Request $request, $id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    $newStatus = $request->status;
    $nowIndonesia = now('Asia/Jakarta');

    // Close previous status log
    $lastLog = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
      ->whereNull('end_time')
      ->latest('start_time')
      ->first();

    if ($lastLog) {
      $lastLog->update([
        'end_time' => $nowIndonesia,
        'duration_seconds' => $nowIndonesia->diffInSeconds($lastLog->start_time)
      ]);
    }

    // Create new status log
    \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::create([
      'monitoring_id' => $id,
      'status' => $newStatus,
      'start_time' => $nowIndonesia,
      'created_at' => $nowIndonesia
    ]);

    // Update monitoring status
    $monitoring->update([
      'current_status' => $newStatus,
      'updated_at' => $nowIndonesia
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Status updated successfully'
    ]);
  }

  public function updateQtyOk(Request $request, $id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    $qty = $request->qty ?? 1;
    $monitoring->increment('qty_ok', $qty);
    $monitoring->increment('qty_actual', $qty);

    // Record OK timestamp for cycle time calculation
    OeeCalculationService::recordOkTimestamp($id);

    // Get updated metrics
    $metrics = OeeCalculationService::getRealtimeMetrics($id);

    return response()->json([
      'success' => true,
      'qty_ok' => $monitoring->qty_ok,
      'qty_actual' => $monitoring->qty_actual,
      'metrics' => $metrics
    ]);
  }

  public function saveDowntime(Request $request, $id)
  {
    $request->validate([
      'downtime_type' => 'required|string',
      'downtime_reason' => 'required|string',
    ]);

    $nowIndonesia = now('Asia/Jakarta');

    \App\Modules\Production\Models\ProductionProcess\ProductionDowntime::create([
      'monitoring_id' => $id,
      'downtime_type' => $request->downtime_type,
      'downtime_reason' => $request->downtime_reason,
      'start_time' => $nowIndonesia,
      'notes' => $request->notes,
      'created_at' => $nowIndonesia
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
    $nowIndonesia = now('Asia/Jakarta');
    $qty = $request->qty;

    \App\Modules\Production\Models\ProductionProcess\ProductionNg::create([
      'monitoring_id' => $id,
      'ng_type' => $request->ng_type,
      'ng_reason' => $request->ng_reason,
      'qty' => $qty,
      'notes' => $request->notes,
      'created_at' => $nowIndonesia
    ]);

    $monitoring->increment('qty_ng', $qty);
    $monitoring->increment('qty_actual', $qty);

    // Get updated metrics (Quality and Performance will change)
    $metrics = OeeCalculationService::getRealtimeMetrics($id);

    return response()->json([
      'success' => true,
      'message' => 'NG recorded successfully',
      'qty_ng' => $monitoring->qty_ng,
      'qty_actual' => $monitoring->qty_actual,
      'metrics' => $metrics
    ]);
  }

  public function checkMqttNgSignal($id)
  {
    $cacheKey = "mqtt_ng_signal_{$id}";
    $signal = Cache::get($cacheKey);

    if ($signal) {
      // Remove the signal after retrieving it
      Cache::forget($cacheKey);
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
    $cacheKey = "mqtt_show_downtime_form_{$id}";
    $signal = Cache::get($cacheKey);

    if ($signal) {
      // Remove the signal after retrieving it
      Cache::forget($cacheKey);
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
    $cacheKey = "mqtt_status_signal_{$id}";
    $signal = Cache::get($cacheKey);

    if ($signal) {
      // Remove the signal after retrieving it
      Cache::forget($cacheKey);
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

  /**
   * Send MQTT signal with unified payload format
   */
  public function sendMqttSignal(Request $request, $id)
  {
    $request->validate([
      'trx_type' => 'required|in:status,qty_ok,ng,downtime',
    ]);

    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::with('machine')
      ->findOrFail($id);

    if (!$monitoring->machine) {
      return response()->json([
        'success' => false,
        'message' => 'Machine not found for this monitoring'
      ], 404);
    }

    $machineCode = $monitoring->machine->machine_code;
    $trxType = $request->trx_type;

    // Build payload based on transaction type
    $payload = [
      'trx_type' => $trxType,
      'mesin' => $machineCode,
      'time' => now('Asia/Jakarta')->format('H:i:s')
    ];

    // Add specific fields based on trx_type
    switch ($trxType) {
      case 'status':
        $request->validate(['status' => 'required|in:Ready,Running,Downtime,Stopped,Paused']);
        $payload['status'] = $request->status;
        break;

      case 'qty_ok':
        $payload['qty'] = $request->qty ?? 1;
        break;

      case 'ng':
        $request->validate([
          'qty' => 'required|integer|min:1',
          'ng_type' => 'required|string',
          'ng_reason' => 'required|string'
        ]);
        $payload['qty'] = $request->qty;
        $payload['ng_type'] = $request->ng_type;
        $payload['ng_reason'] = $request->ng_reason;
        break;

      case 'downtime':
        $request->validate([
          'downtime_type' => 'required|string',
          'downtime_reason' => 'required|string'
        ]);
        $payload['downtime_type'] = $request->downtime_type;
        $payload['downtime_reason'] = $request->downtime_reason;
        break;
    }

    // Send MQTT message
    try {
      $mqttService = new \App\Services\MqttService();

      if (!$mqttService->connect()) {
        return response()->json([
          'success' => false,
          'message' => 'Failed to connect to MQTT broker'
        ], 500);
      }

      $topic = "production/{$machineCode}/signal";
      $message = json_encode($payload);

      $mqttService->publish($topic, $message);
      $mqttService->disconnect();

      \Log::info("MQTT Signal Sent", [
        'topic' => $topic,
        'payload' => $payload
      ]);

      return response()->json([
        'success' => true,
        'message' => 'MQTT signal sent successfully',
        'topic' => $topic,
        'payload' => $payload
      ]);
    } catch (\Exception $e) {
      \Log::error('Failed to send MQTT signal: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Failed to send MQTT signal: ' . $e->getMessage()
      ], 500);
    }
  }
}
