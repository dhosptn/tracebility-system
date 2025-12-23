<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use App\Modules\Production\Models\ProductionProcess\ProductionMonitoring;
use App\Modules\Production\Models\ProductionProcess\ProductionStatusLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MqttProductionListener extends Command
{
  protected $signature = 'mqtt:production-listener';
  protected $description = 'Listen to MQTT messages for production monitoring';

  protected $mqttService;

  public function __construct()
  {
    parent::__construct();
    $this->mqttService = new MqttService();
  }

  public function handle()
  {
    $this->info('Starting MQTT Production Listener...');
    $this->info('Configuration:');
    $this->line('  Host: ' . env('MQTT_HOST', '127.0.0.1'));
    $this->line('  Port: ' . env('MQTT_PORT', 1883));
    $this->line('');

    if (!$this->mqttService->connect()) {
      $this->error('Failed to connect to MQTT broker');
      $this->error('Reason: ' . $this->mqttService->getLastError());
      $this->error('Please ensure:');
      $this->error('  1. MQTT broker is running (Mosquitto)');
      $this->error('  2. Host and port are correct in .env');
      $this->error('  3. Firewall is not blocking port 1883');
      $this->error('');
      $this->error('To start Mosquitto with Docker:');
      $this->error('  docker-compose up -d mosquitto');
      return 1;
    }

    $this->info('✓ Connected to MQTT broker');
    $this->line('');

    // Subscribe to production topics
    $this->subscribeToTopics();
    $this->line('');
    $this->info('Listening for MQTT messages... (Press Ctrl+C to stop)');
    $this->line('');

    // Keep the listener running
    try {
      $lastHeartbeat = now();
      while (true) {
        // Use non-blocking loop so we can process pending actions regardless of new messages
        $this->mqttService->loop(false);
        $this->processPendingActions();
        
        // Heartbeat log every 30s to confirm loop is alive
        if (now()->diffInSeconds($lastHeartbeat) >= 30) {
            $this->line('<fg=gray>  [Log] Listener pulse: ' . now('Asia/Jakarta')->toTimeString() . ' - Checking pending signals...</>');
            $lastHeartbeat = now();
        }
        
        usleep(100000); // 100ms delay
      }
    } catch (\Exception $e) {
      $this->error('Error in listener loop: ' . $e->getMessage());
      Log::error('MQTT Listener error: ' . $e->getMessage());
      return 1;
    }
  }


  protected function processPendingActions()
  {
      $now = now('Asia/Jakarta');
      $nowStr = $now->toDateTimeString();
      
      // Get signals that are ready to be executed
      $pendingSignals = DB::table('t_production_pending_signals')
        ->where('is_processed', 0) // Use integer 0 explicitly
        ->where('execute_at', '<=', $nowStr)
        ->orderBy('execute_at', 'asc')
        ->orderBy('id', 'asc')
        ->get();

      if ($pendingSignals->isEmpty()) {
          // Monitor queue every 10s even if empty to show it's alive
          static $lastEmptyLog = 0;
          if (time() - $lastEmptyLog >= 10) {
              $total = DB::table('t_production_pending_signals')->where('is_processed', 0)->count();
              if ($total > 0) {
                  $next = DB::table('t_production_pending_signals')->where('is_processed', 0)->orderBy('execute_at', 'asc')->first();
                  $this->line("<fg=yellow>  [Queue] {$total} signals waiting. Next: {$next->execute_at} | Now: {$nowStr}</>");
              }
              $lastEmptyLog = time();
          }
          return;
      }

      $this->info("Found " . $pendingSignals->count() . " pending signals to process at " . $now->toTimeString());

      foreach ($pendingSignals as $signal) {
        try {
          $scheduledTime = \Carbon\Carbon::parse($signal->execute_at, 'Asia/Jakarta');
          $this->info("▶ Processing {$signal->trx_type} for {$signal->machine_code} (Scheduled: " . $scheduledTime->toTimeString() . ")");
          
          $payload = json_decode($signal->payload, true);
          
          // Ensure we use the original payload time as the effective timestamp
          $this->processUnifiedData($payload);

          // Mark as processed
          DB::table('t_production_pending_signals')
            ->where('id', $signal->id)
            ->update([
              'is_processed' => true,
              'updated_at' => now('Asia/Jakarta')
            ]);
            
          $this->info("✓ Successfully processed and marked as done.");
        } catch (\Exception $e) {
          $this->error("✖ Error processing pending signal {$signal->id}: " . $e->getMessage());
          Log::error("Pending Signal Error: " . $e->getMessage(), ['signal_id' => $signal->id]);
        }
      }

      // Cleanup old processed signals (keep last 1 hour)
      DB::table('t_production_pending_signals')
          ->where('is_processed', true)
          ->where('updated_at', '<', now('Asia/Jakarta')->subHour())
          ->delete();
  }

  protected function subscribeToTopics()
  {
    // Subscribe to unified production topic with trx_type
    $this->mqttService->subscribe('production/+/signal', function ($topic, $message) {
      $this->handleUnifiedSignal($topic, $message);
    });

    // Keep backward compatibility with old topics
    $this->mqttService->subscribe('production/+/qty_ok', function ($topic, $message) {
      $this->handleQtyOk($topic, $message);
    });

    $this->mqttService->subscribe('production/+/status', function ($topic, $message) {
      $this->handleStatus($topic, $message);
    });

    $this->mqttService->subscribe('production/+/ng', function ($topic, $message) {
      $this->handleNg($topic, $message);
    });

    $this->info('Subscribed to production topics (unified + legacy)');
  }

  /**
   * Handle unified signal with trx_type
   * Expected payload: { trx_type: "status", mesin: "xxx", status: "Running", time: "12:00:00" }
   */
  protected function handleUnifiedSignal($topic, $message)
  {
    try {
      $this->info("DEBUG: Received unified signal on topic: {$topic}");
      $this->info("DEBUG: Message content: {$message}");

      $data = json_decode($message, true);

      if (!$data) {
        $this->error("DEBUG: Failed to parse JSON: {$message}");
        Log::error("Unified Signal: Failed to parse JSON: {$message}");
        return;
      }

      // Check for future time
      $time = $data['time'] ?? null;
      if ($time) {
          $nowIndonesia = now('Asia/Jakarta');
          // Assume time is for TODAY.
          $payloadTime = \Carbon\Carbon::parse($nowIndonesia->format('Y-m-d') . ' ' . $time, 'Asia/Jakarta');
          
          if ($nowIndonesia->lt($payloadTime)) {
             $mesin = $data['mesin'] ?? $data['machine_code'] ?? 'Unknown';
             $trxType = $data['trx_type'] ?? 'unknown';
             $this->info("⏳ Saving delayed signal for {$mesin} until " . $payloadTime->format('H:i:s') . " (Current: " . $nowIndonesia->format('H:i:s') . ")");

             DB::table('t_production_pending_signals')->insert([
               'machine_code' => $mesin,
               'trx_type' => $trxType,
               'payload' => $message,
               'execute_at' => $payloadTime->toDateTimeString(),
               'is_processed' => false,
               'created_at' => now(),
               'updated_at' => now()
             ]);
             return;
          }
      }

      $this->processUnifiedData($data);

    } catch (\Exception $e) {
      Log::error('Error handling unified signal: ' . $e->getMessage());
      $this->error("ERROR: " . $e->getMessage());
    }
  }

  protected function processUnifiedData($data)
  {
      $rawTrxType = $data['trx_type'] ?? null;
      $mesin = $data['mesin'] ?? $data['machine_code'] ?? null;
      
      // Normalize trx_type to lowercase and fix common typos
      $trxType = strtolower($rawTrxType ?? '');
      if ($trxType === 'statys') $trxType = 'status';

      // Parse timestamp
      $nowIndonesia = now('Asia/Jakarta');
      $effectiveTime = isset($data['time']) 
          ? \Carbon\Carbon::parse($nowIndonesia->format('Y-m-d') . ' ' . $data['time'], 'Asia/Jakarta')
          : $nowIndonesia;

      $this->info("DEBUG: Processing - trx_type: {$trxType} (original: {$rawTrxType}), mesin: {$mesin}, time: {$effectiveTime->toTimeString()}");

      if (!$trxType) {
        Log::warning('Unified Signal: trx_type not provided');
        return;
      }

      // Find monitoring_id by machine code
      $monitoringId = $this->findMonitoringIdByMachine($mesin);
      if (!$monitoringId) {
        Log::warning("Unified Signal: No active monitoring found for machine: {$mesin}");
        $this->error("DEBUG: No active monitoring found for machine: {$mesin}");
        return;
      }

      // Route to appropriate handler based on trx_type
      switch ($trxType) {
        case 'status':
          $status = $data['status'] ?? null;
          if ($status) {
            $this->handleStatusUpdate($monitoringId, $status, $effectiveTime);
          }
          break;


        case 'qty_ok':
          $qty = $data['qty'] ?? 1;
          $this->handleQtyOkUpdate($monitoringId, $qty, $effectiveTime);
          break;

        case 'ng':
          $qty = $data['qty'] ?? 1;
          $ngType = $data['ng_type'] ?? 'Unknown';
          $ngReason = $data['ng_reason'] ?? 'From MQTT';
          $this->handleNgUpdate($monitoringId, $qty, $ngType, $ngReason, $effectiveTime);
          break;

        case 'downtime':
          $downtimeType = $data['downtime_type'] ?? 'Unknown';
          $downtimeReason = $data['downtime_reason'] ?? 'From MQTT';
          $this->handleDowntimeUpdate($monitoringId, $downtimeType, $downtimeReason, $effectiveTime);
          break;

        default:
          Log::warning("Unified Signal: Unknown trx_type: {$trxType}");
          $this->error("DEBUG: Unknown trx_type: {$trxType}");
      }
  }

  /**
   * Find active monitoring ID by machine code
   */
  protected function findMonitoringIdByMachine($machineCode)
  {
    if (!$machineCode) {
      return null;
    }

    // Find active monitoring by machine code
    $monitoring = ProductionMonitoring::whereHas('machine', function ($query) use ($machineCode) {
      $query->where('machine_code', $machineCode);
    })
      ->where('is_active', 1)
      ->latest('start_time')
      ->first();

    return $monitoring ? $monitoring->monitoring_id : null;
  }

  /**
   * Handle status update from unified signal
   */
  protected function handleStatusUpdate($monitoringId, $status, $timestamp = null)
  {
    try {
      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        return;
      }

      // Normalize status values
      $statusMap = [
        'Run' => 'Running',
        'Stop' => 'Stopped',
        'Running' => 'Running',
        'Stopped' => 'Stopped',
        'Ready' => 'Ready',
        'Paused' => 'Paused',
        'Downtime' => 'Downtime',
        'Start' => 'Running',
        'START' => 'Running'
      ];

      $normalizedStatus = $statusMap[$status] ?? $status;
      $this->info("DEBUG: Normalized status: {$status} -> {$normalizedStatus}");
      
      // Use provided timestamp if available, otherwise use now()
      $effectiveTime = $timestamp instanceof \Carbon\Carbon ? $timestamp : now('Asia/Jakarta');

      // Close previous status log
      $lastLog = ProductionStatusLog::where('monitoring_id', $monitoringId)
        ->whereNull('end_time')
        ->latest('start_time')
        ->first();

      if ($lastLog) {
        // Calculate duration in seconds - ensure positive integer
        $durationSeconds = (int)max(0, floor(abs($effectiveTime->diffInSeconds($lastLog->start_time))));

        $lastLog->update([
          'end_time' => $effectiveTime,
          'duration_seconds' => $durationSeconds
        ]);

        Log::info("Status log closed", [
          'monitoring_id' => $monitoringId,
          'previous_status' => $lastLog->status,
          'start_time' => $lastLog->start_time->toIso8601String(),
          'end_time' => $effectiveTime->toIso8601String(),
          'duration_seconds' => $durationSeconds
        ]);
      }
      
      // Create new status log
      ProductionStatusLog::create([
        'monitoring_id' => $monitoringId,
        'status' => $normalizedStatus,
        'start_time' => $effectiveTime,
        'created_at' => $effectiveTime
      ]);

      // Update monitoring status
      $monitoring->update([
        'current_status' => $normalizedStatus,
        'updated_at' => $effectiveTime
      ]);

      // Signal frontend
      if ($normalizedStatus === 'Downtime') {
        Cache::put("mqtt_show_downtime_form_{$monitoringId}", true, 300);
      }

      $finalDuration = null;
      if ($normalizedStatus === 'Finish') {
          // Re-fetch the last log which should be the one we just closed
          $lastActiveLog = ProductionStatusLog::where('monitoring_id', $monitoringId)
              ->where('status', '!=', 'Finish')
              ->whereNotNull('end_time')
              ->orderBy('end_time', 'desc')
              ->first();
          if ($lastActiveLog) {
              $finalDuration = $lastActiveLog->duration_seconds;
          }
      }

      Cache::put("mqtt_status_signal_{$monitoringId}", [
        'status' => $normalizedStatus,
        'timestamp' => $effectiveTime->toIso8601String(),
        'final_duration' => $finalDuration
      ], 60);

      $this->info("✓ Status updated for monitoring {$monitoringId}: {$normalizedStatus} (Time: {$effectiveTime->toDateTimeString()})");
      Log::info("MQTT Status: Monitoring {$monitoringId}, Status: {$normalizedStatus}, Effective Time: {$effectiveTime}");
    } catch (\Exception $e) {
      Log::error('Error handling status update: ' . $e->getMessage());
    }
  }

  /**
   * Handle qty OK update from unified signal
   */
  protected function handleQtyOkUpdate($monitoringId, $qty, $timestamp = null)
  {
    try {
      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        return;
      }

      // VALIDATION: Only accept qty_ok when status is Running or START
      $currentStatus = strtoupper($monitoring->current_status);
      if (!in_array($currentStatus, ['RUNNING', 'START'])) {
        $this->warn("⚠ QTY OK signal REJECTED for monitoring {$monitoringId}: Status is '{$monitoring->current_status}'");
        Log::warning("MQTT QTY OK REJECTED: Monitoring {$monitoringId}, Current Status: {$monitoring->current_status}");
        return;
      }

      $effectiveTime = $timestamp instanceof \Carbon\Carbon ? $timestamp : now('Asia/Jakarta');

      $monitoring->increment('qty_ok', $qty);
      $monitoring->increment('qty_actual', $qty);
      $monitoring->refresh(); // Refresh to get updated values for finish check

      // Record OK timestamp for cycle time calculation
      $this->recordOkTimestamp($monitoringId, $effectiveTime);

      // Check if Finished
      if ($monitoring->qty_ok >= $monitoring->wo_qty && $monitoring->current_status !== 'Finish') {
          $this->info("✔ Production finished for monitoring {$monitoringId} (qty_ok: {$monitoring->qty_ok} / {$monitoring->wo_qty})");
          
          // Use the internal handleStatusUpdate to handle all the logging and cache signals
          \App\Modules\Production\Services\ProductionFinishService::finishMonitoring($monitoringId);
      } else {
          // Broadcast to frontend (if not finished, handleStatusUpdate already signals if it did finish)
          Cache::put("mqtt_qty_ok_{$monitoringId}", [
            'qty_ok' => $monitoring->qty_ok,
            'qty_actual' => $monitoring->qty_actual,
            'timestamp' => $effectiveTime->toIso8601String()
          ], 60);
      }

      $this->info("✓ QTY OK updated for monitoring {$monitoringId}: +{$qty} (Time: {$effectiveTime->toTimeString()})");
      Log::info("MQTT QTY OK: Monitoring {$monitoringId}, Qty: {$qty}, Time: {$effectiveTime}");
    } catch (\Exception $e) {
      Log::error('Error handling qty OK update: ' . $e->getMessage());
    }
  }

  /**
   * Handle NG update from unified signal
   */
  protected function handleNgUpdate($monitoringId, $qty, $ngType, $ngReason, $timestamp = null)
  {
    try {
      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        $this->error("DEBUG: Monitoring ID {$monitoringId} not found for NG update");
        return;
      }

      // VALIDATION: Only accept NG when status is Running or START
      $currentStatus = strtoupper($monitoring->current_status);
      if (!in_array($currentStatus, ['RUNNING', 'START'])) {
        $this->warn("⚠ NG signal REJECTED for monitoring {$monitoringId}: Status is '{$monitoring->current_status}'");
        Log::warning("MQTT NG REJECTED: Monitoring {$monitoringId}, Current Status: {$monitoring->current_status}");
        return;
      }

      $effectiveTime = $timestamp instanceof \Carbon\Carbon ? $timestamp : now('Asia/Jakarta');

      // DIRECTLY UPDATE DATABASE - Create NG record
      \App\Modules\Production\Models\ProductionProcess\ProductionNg::create([
        'monitoring_id' => $monitoringId,
        'ng_type' => $ngType,
        'ng_reason' => $ngReason,
        'qty' => $qty,
        'notes' => 'Auto-created from MQTT signal',
        'created_at' => $effectiveTime
      ]);

      // Update monitoring quantities
      $monitoring->increment('qty_ng', $qty);
      $monitoring->increment('qty_actual', $qty);

      // Signal frontend for real-time update AND show modal for confirmation
      Cache::put("mqtt_ng_signal_{$monitoringId}", [
        'show' => true, // Show modal for user confirmation/review
        'qty' => $qty,
        'ng_type' => $ngType,
        'ng_reason' => $ngReason,
        'qty_ng' => $monitoring->qty_ng,
        'qty_actual' => $monitoring->qty_actual,
        'auto_saved' => true, // Indicate it's already saved to DB
        'timestamp' => $effectiveTime->toIso8601String()
      ], 60);

      $this->info("✓ NG updated DIRECTLY to database for monitoring {$monitoringId}: qty {$qty}, type: {$ngType} (Time: {$effectiveTime->toTimeString()})");
      Log::info("MQTT NG: Monitoring {$monitoringId}, Qty: {$qty}, Type: {$ngType}, Time: {$effectiveTime}");
    } catch (\Exception $e) {
      Log::error('Error handling NG update: ' . $e->getMessage());
      $this->error("ERROR handling NG: " . $e->getMessage());
    }
  }

  /**
   * Handle downtime update from unified signal
   */
  protected function handleDowntimeUpdate($monitoringId, $downtimeType, $downtimeReason, $timestamp = null)
  {
    try {
      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        $this->error("DEBUG: Monitoring ID {$monitoringId} not found for downtime update");
        return;
      }

      $effectiveTime = $timestamp instanceof \Carbon\Carbon ? $timestamp : now('Asia/Jakarta');

      // DIRECTLY UPDATE DATABASE - Create downtime record
      \App\Modules\Production\Models\ProductionProcess\ProductionDowntime::create([
        'monitoring_id' => $monitoringId,
        'downtime_type' => $downtimeType,
        'downtime_reason' => $downtimeReason,
        'start_time' => $effectiveTime,
        'notes' => 'Auto-created from MQTT signal',
        'created_at' => $effectiveTime
      ]);

      // FORCE UPDATE STATUS TO DOWNTIME
      if ($monitoring->current_status !== 'Downtime') {
        $this->handleStatusUpdate($monitoringId, 'Downtime', $effectiveTime);
        $this->info("✓ Auto-updated status to Downtime for monitoring {$monitoringId}");
      }

      // Signal frontend for real-time update AND show modal for confirmation
      Cache::put("mqtt_downtime_signal_{$monitoringId}", [
        'show' => true, // Show modal for user confirmation/review
        'downtime_type' => $downtimeType,
        'downtime_reason' => $downtimeReason,
        'auto_saved' => true, // Indicate it's already saved to DB
        'timestamp' => $effectiveTime->toIso8601String()
      ], 60);

      $this->info("✓ Downtime updated DIRECTLY to database for monitoring {$monitoringId}: {$downtimeType} (Time: {$effectiveTime->toTimeString()})");
      Log::info("MQTT Downtime: Monitoring {$monitoringId}, Type: {$downtimeType}, Time: {$effectiveTime}");
    } catch (\Exception $e) {
      Log::error('Error handling downtime update: ' . $e->getMessage());
      $this->error("ERROR handling downtime: " . $e->getMessage());
    }
  }

  protected function handleQtyOk($topic, $message)
  {
    try {
      $this->info("DEBUG: Received message on topic: {$topic}");
      $this->info("DEBUG: Message content: {$message}");

      $data = json_decode($message, true);

      if (!$data) {
        $this->error("DEBUG: Failed to parse JSON: {$message}");
        Log::error("QTY OK: Failed to parse JSON: {$message}");
        return;
      }

      $monitoringId = $data['monitoring_id'] ?? null;
      $qty = $data['qty'] ?? 1;

      $this->info("DEBUG: Parsed - monitoring_id: {$monitoringId}, qty: {$qty}");

      if (!$monitoringId) {
        Log::warning('QTY OK: monitoring_id not provided');
        return;
      }

      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        Log::warning("QTY OK: Monitoring ID {$monitoringId} not found");
        $this->error("DEBUG: Monitoring ID {$monitoringId} not found in database");
        return;
      }

      $monitoring->increment('qty_ok', $qty);
      $monitoring->increment('qty_actual', $qty);
      $monitoring->refresh(); // Refresh to get updated values for finish check

      $effectiveTime = isset($data['time']) 
          ? \Carbon\Carbon::parse(now('Asia/Jakarta')->format('Y-m-d') . ' ' . $data['time'], 'Asia/Jakarta')
          : now('Asia/Jakarta');

      // Record OK timestamp for cycle time calculation
      $this->recordOkTimestamp($monitoringId, $effectiveTime);

      // Check if Finished
      if ($monitoring->qty_ok >= $monitoring->wo_qty && $monitoring->current_status !== 'Finish') {
          $this->info("✔ Production finished (Legacy) for monitoring {$monitoringId} (qty_ok: {$monitoring->qty_ok} / {$monitoring->wo_qty})");
          \App\Modules\Production\Services\ProductionFinishService::finishMonitoring($monitoringId);
      } else {
          // Broadcast to frontend via cache/event
          Cache::put("mqtt_qty_ok_{$monitoringId}", [
            'qty_ok' => $monitoring->qty_ok,
            'qty_actual' => $monitoring->qty_actual,
            'timestamp' => $effectiveTime->toIso8601String()
          ], 60);
      }

      $this->info("✓ QTY OK updated for monitoring {$monitoringId}: +{$qty} (Time: {$effectiveTime->toTimeString()})");
      Log::info("MQTT QTY OK: Monitoring {$monitoringId}, Qty: {$qty}, Time: {$effectiveTime}");
    } catch (\Exception $e) {
      Log::error('Error handling QTY OK: ' . $e->getMessage());
      $this->error("ERROR: " . $e->getMessage());
    }
  }

  protected function handleStatus($topic, $message)
  {
    try {
      $this->info("DEBUG: Received Status message on topic: {$topic}");
      $this->info("DEBUG: Message content: {$message}");

      $data = json_decode($message, true);
      $monitoringId = $data['monitoring_id'] ?? null;
      $status = $data['status'] ?? null; // Ready, Running, Downtime, Stop, Run

      if (!$monitoringId || !$status) {
        Log::warning('Status: monitoring_id or status not provided');
        return;
      }

      // Normalize status values
      $statusMap = [
        'Run' => 'Running',
        'Stop' => 'Stopped',
        'Running' => 'Running',
        'Stopped' => 'Stopped',
        'Ready' => 'Ready',
        'Paused' => 'Paused',
        'Downtime' => 'Downtime'
      ];

      $normalizedStatus = $statusMap[$status] ?? $status;

      $this->info("DEBUG: Parsed - monitoring_id: {$monitoringId}, status: {$status} -> {$normalizedStatus}");

      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        Log::warning("Status: Monitoring ID {$monitoringId} not found");
        $this->error("DEBUG: Monitoring ID {$monitoringId} not found in database");
        return;
      }

      // Get current time in Indonesia timezone (WIB - UTC+7)
      // ================================
      // VALIDASI WAKTU PAYLOAD
      // ================================
      $nowIndonesia = now('Asia/Jakarta');
      $payloadTime = $nowIndonesia;

      if (!empty($data['time'])) {
        $payloadTime = \Carbon\Carbon::parse(
        $nowIndonesia->format('Y-m-d') . ' ' . $data['time'],
        'Asia/Jakarta'
    );

    // ⛔ BELUM WAKTUNYA → JANGAN PROSES STATUS
    if ($nowIndonesia->lt($payloadTime)) {
        Log::info('Status ditunda karena waktu belum tercapai', [
            'monitoring_id' => $monitoringId,
            'status' => $normalizedStatus,
            'now' => $nowIndonesia->toDateTimeString(),
            'payload_time' => $payloadTime->toDateTimeString()
        ]);
        return;
    }
}


      // Signal frontend to update status
      Cache::put("mqtt_status_signal_{$monitoringId}", [
        'status' => $normalizedStatus,
        'timestamp' => $payloadTime->toIso8601String()
      ], 60);

      // LEGACY: Directly update DB using payloadTime
      ProductionStatusLog::create([
        'monitoring_id' => $monitoringId,
        'status' => $normalizedStatus,
        'start_time' => $payloadTime,
        'created_at' => $payloadTime
      ]);

      $monitoring->update([
        'current_status' => $normalizedStatus,
        'updated_at' => $payloadTime,
      ]);

      $this->info("✓ Status updated for monitoring {$monitoringId}: {$normalizedStatus} (Time: {$payloadTime->toTimeString()})");
      Log::info("MQTT Status: Monitoring {$monitoringId}, Status: {$normalizedStatus}, Legacy Time: {$payloadTime}");
    } catch (\Exception $e) {
      Log::error('Error handling Status: ' . $e->getMessage());
      $this->error("ERROR: " . $e->getMessage());
    }
  }

  protected function handleNg($topic, $message)
  {
    try {
      $this->info("DEBUG: Received NG message on topic: {$topic}");
      $this->info("DEBUG: Message content: {$message}");

      $data = json_decode($message, true);

      if (!$data) {
        $this->error("DEBUG: Failed to parse JSON: {$message}");
        Log::error("NG: Failed to parse JSON: {$message}");
        return;
      }

      $monitoringId = $data['monitoring_id'] ?? null;
      $qty = $data['qty'] ?? 1;

      $this->info("DEBUG: Parsed - monitoring_id: {$monitoringId}, qty: {$qty}");

      if (!$monitoringId) {
        Log::warning('NG: monitoring_id not provided');
        return;
      }

      $monitoring = ProductionMonitoring::find($monitoringId);
      if (!$monitoring) {
        Log::warning("NG: Monitoring ID {$monitoringId} not found");
        $this->error("DEBUG: Monitoring ID {$monitoringId} not found in database");
        return;
      }

      $effectiveTime = isset($data['time']) 
          ? \Carbon\Carbon::parse(now('Asia/Jakarta')->format('Y-m-d') . ' ' . $data['time'], 'Asia/Jakarta')
          : now('Asia/Jakarta');

      // Signal frontend to show NG form with qty from MQTT
      Cache::put("mqtt_ng_signal_{$monitoringId}", [
        'show' => true,
        'qty' => $qty,
        'timestamp' => $effectiveTime->toIso8601String()
      ], 300);

      $this->info("✓ NG signal received for monitoring {$monitoringId}: qty {$qty} (Time: {$effectiveTime->toTimeString()})");
      Log::info("MQTT NG: Monitoring {$monitoringId}, Qty: {$qty}, Time: {$effectiveTime}");
    } catch (\Exception $e) {
      Log::error('Error handling NG: ' . $e->getMessage());
      $this->error("ERROR: " . $e->getMessage());
    }
  }

  /**
   * Record OK timestamp for cycle time calculation
   */
  private function recordOkTimestamp($monitoringId, $timestamp = null)
  {
    \App\Modules\Production\Services\OeeCalculationService::recordOkTimestamp($monitoringId, $timestamp);
  }
}
