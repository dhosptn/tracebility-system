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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

    // Deactivate existing active monitorings for this machine
    \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::where('machine_id', $request->machine_id)
      ->where('is_active', 1)
      ->update(['is_active' => 0]);

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
      'created_by' => Auth::check() ? Auth::user()->name : 'system',
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

    // AUTO-FINISH CHECK: If target reached but status not Finish, finish it now
    if ($monitoring->qty_ok >= $monitoring->wo_qty && $monitoring->wo_qty > 0 && $monitoring->current_status !== 'Finish') {
        \App\Modules\Production\Services\ProductionFinishService::finishMonitoring($id);
        $monitoring->refresh();
    }

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

    // Get current status start time
    $currentStatusLog = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
        ->whereNull('end_time')
        ->latest('start_time')
        ->first();
    $statusStartTime = $currentStatusLog ? $currentStatusLog->start_time->toIso8601String() : null;

    $finalDuration = null;
    if ($monitoring->current_status === 'Finish') {
        $lastActiveLog = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
            ->where('status', '!=', 'Finish')
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->first();
        if ($lastActiveLog) {
            $finalDuration = $lastActiveLog->duration_seconds;
        }
    }

    return response()->json([
      'wo_qty' => $monitoring->wo_qty,
      'qty_actual' => $monitoring->qty_actual,
      'qty_ng' => $monitoring->qty_ng,
      'qty_ok' => $monitoring->qty_ok,
      'current_status' => $monitoring->current_status,
      'current_status_start_time' => $statusStartTime,
      'final_duration' => $finalDuration,
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

    // Check if Finished 
    if ($monitoring->qty_ok >= $monitoring->wo_qty && $monitoring->current_status !== 'Finish') {
        \App\Modules\Production\Services\ProductionFinishService::finishMonitoring($id);
        $monitoring->refresh();
    }

    // Get updated metrics
    $metrics = OeeCalculationService::getRealtimeMetrics($id);

    return response()->json([
      'success' => true,
      'qty_ok' => $monitoring->qty_ok,
      'qty_actual' => $monitoring->qty_actual,
      'current_status' => $monitoring->current_status,
      'metrics' => $metrics
    ]);
  }

  public function saveDowntime(Request $request, $id)
  {
    try {
      $request->validate([
        'downtime_type' => 'required|string',
        'downtime_reason' => 'required|string',
      ]);

      $nowIndonesia = now('Asia/Jakarta');
      $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

      // Save Downtime Record
      \App\Modules\Production\Models\ProductionProcess\ProductionDowntime::create([
        'monitoring_id' => $id,
        'downtime_type' => $request->downtime_type,
        'downtime_reason' => $request->downtime_reason,
        'start_time' => $nowIndonesia,
        'notes' => $request->notes,
        'created_at' => $nowIndonesia
      ]);

      // FORCE STATUS UPDATE TO DOWNTIME (if not already)
      if ($monitoring->current_status !== 'Downtime') {
        // Close previous log
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

        // Create new 'Downtime' status log
        \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::create([
          'monitoring_id' => $id,
          'status' => 'Downtime',
          'start_time' => $nowIndonesia,
          'created_at' => $nowIndonesia
        ]);

        // Update monitoring status
        $monitoring->update([
          'current_status' => 'Downtime',
          'updated_at' => $nowIndonesia
        ]);
        
        // Clear checking cache to update frontend immediately
        Cache::forget("mqtt_status_signal_{$id}"); 
        Cache::put("mqtt_status_signal_{$id}", ['show' => true, 'status' => 'Downtime'], 10);
      }

      return response()->json([
        'success' => true,
        'message' => 'Downtime recorded successfully'
      ]);
    } catch (\Exception $e) {
      \Log::error("Save Downtime Failed: " . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to save downtime: ' . $e->getMessage()
      ], 500);
    }
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
        'qty' => $signal['qty'] ?? 1,
        'ng_type' => $signal['ng_type'] ?? null,
        'ng_reason' => $signal['ng_reason'] ?? null,
        'qty_ng' => $signal['qty_ng'] ?? null,
        'qty_actual' => $signal['qty_actual'] ?? null,
        'auto_saved' => $signal['auto_saved'] ?? false,
        'timestamp' => $signal['timestamp'] ?? null
      ]);
    }

    return response()->json([
      'show' => false,
      'qty' => 1,
      'qty_ng' => null,
      'qty_actual' => null
    ]);
  }

  public function checkMqttDowntimeSignal($id)
  {
    $cacheKey = "mqtt_downtime_signal_{$id}";
    $signal = Cache::get($cacheKey);

    if ($signal) {
      // Remove the signal after retrieving it
      Cache::forget($cacheKey);
      return response()->json([
        'show' => $signal['show'] ?? false,
        'downtime_type' => $signal['downtime_type'] ?? null,
        'downtime_reason' => $signal['downtime_reason'] ?? null,
        'auto_saved' => $signal['auto_saved'] ?? false,
        'timestamp' => $signal['timestamp'] ?? null
      ]);
    }

    // Check for status-triggered downtime form (from status=Downtime signal)
    $formCacheKey = "mqtt_show_downtime_form_{$id}";
    $showForm = Cache::get($formCacheKey);

    if ($showForm) {
      Cache::forget($formCacheKey);
      return response()->json([
        'show' => true,
        'downtime_type' => null,
        'downtime_reason' => null,
        'auto_saved' => false
      ]);
    }

    return response()->json([
      'show' => false,
      'downtime_type' => null,
      'downtime_reason' => null
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
   * Get current status for production monitoring (for Current Time display)
   */
  public function getCurrentStatus($id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    $currentStatusLog = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
        ->whereNull('end_time')
        ->latest('start_time')
        ->first();

    $finalDuration = null;
    if ($monitoring->current_status === 'Finish') {
        $lastActiveLog = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
            ->where('status', '!=', 'Finish')
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->first();
        if ($lastActiveLog) {
            $finalDuration = $lastActiveLog->duration_seconds;
        }
    }

    return response()->json([
      'success' => true,
      'current_status' => $monitoring->current_status,
      'current_status_start_time' => $currentStatusLog ? $currentStatusLog->start_time->toIso8601String() : null,
      'final_duration' => $finalDuration
    ]);
  }

  /**
   * Get accumulated running time for production monitoring
   */
  public function getRunningTime($id)
  {
    $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($id);

    // Calculate total running time from status logs
    $runningLogs = \App\Modules\Production\Models\ProductionProcess\ProductionStatusLog::where('monitoring_id', $id)
      ->where('status', 'Running')
      ->orderBy('start_time', 'asc')
      ->get();

    $totalRunningSeconds = 0;

    foreach ($runningLogs as $log) {
      if ($log->end_time) {
        // Completed running period
        $totalRunningSeconds += $log->duration_seconds ?? 0;
      } else {
        // Currently running - calculate from start_time to now
        $startTime = $log->start_time;
        $now = now('Asia/Jakarta');

        // Calculate difference in seconds
        $diffSeconds = $now->diffInSeconds($startTime);

        // Use abs to handle any timezone issues
        $diffSeconds = abs($diffSeconds);

        // Ensure it's a positive integer
        $diffSeconds = (int)max(0, floor($diffSeconds));

        $totalRunningSeconds += $diffSeconds;

        \Log::info("Running log calculation", [
          'start_time' => $startTime->toIso8601String(),
          'now' => $now->toIso8601String(),
          'diff_seconds' => $diffSeconds,
          'total_so_far' => $totalRunningSeconds
        ]);
      }
    }

    // Ensure total is non-negative integer
    $totalRunningSeconds = (int)max(0, $totalRunningSeconds);

    // Format seconds to HH:mm:ss - ensure non-negative values
    $hours = (int)($totalRunningSeconds / 3600);
    $minutes = (int)(($totalRunningSeconds % 3600) / 60);
    $seconds = (int)($totalRunningSeconds % 60);

    // Ensure all values are non-negative
    $hours = max(0, $hours);
    $minutes = max(0, $minutes);
    $seconds = max(0, $seconds);

    $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

    return response()->json([
      'success' => true,
      'accumulated_seconds' => $totalRunningSeconds,
      'formatted_time' => $formattedTime,
      'current_status' => $monitoring->current_status
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
