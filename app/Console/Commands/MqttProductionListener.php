<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use App\Modules\Production\Models\ProductionProcess\ProductionMonitoring;
use App\Modules\Production\Models\ProductionProcess\ProductionStatusLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
      while (true) {
        $this->mqttService->loop(true);
        usleep(100000); // 100ms delay
      }
    } catch (\Exception $e) {
      $this->error('Error in listener loop: ' . $e->getMessage());
      Log::error('MQTT Listener error: ' . $e->getMessage());
      return 1;
    }
  }

  protected function subscribeToTopics()
  {
    // Subscribe to QTY OK signals
    $this->mqttService->subscribe('production/+/qty_ok', function ($topic, $message) {
      $this->handleQtyOk($topic, $message);
    });

    // Subscribe to Status signals (Ready/Running/Downtime/Stop)
    $this->mqttService->subscribe('production/+/status', function ($topic, $message) {
      $this->handleStatus($topic, $message);
    });

    // Subscribe to NG signals
    $this->mqttService->subscribe('production/+/ng', function ($topic, $message) {
      $this->handleNg($topic, $message);
    });

    $this->info('Subscribed to production topics');
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

      // Broadcast to frontend via cache/event
      Cache::put("mqtt_qty_ok_{$monitoringId}", [
        'qty_ok' => $monitoring->qty_ok,
        'qty_actual' => $monitoring->qty_actual,
        'timestamp' => now()->toIso8601String()
      ], 60);

      $this->info("✓ QTY OK updated for monitoring {$monitoringId}: +{$qty}");
      Log::info("MQTT QTY OK: Monitoring {$monitoringId}, Qty: {$qty}");
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

      // Close previous status log
      $lastLog = ProductionStatusLog::where('monitoring_id', $monitoringId)
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
      ProductionStatusLog::create([
        'monitoring_id' => $monitoringId,
        'status' => $normalizedStatus,
        'start_time' => now(),
        'created_at' => now()
      ]);

      // Update monitoring status
      $monitoring->update([
        'current_status' => $normalizedStatus,
        'updated_at' => now()
      ]);

      // Signal frontend to show form if needed
      if ($normalizedStatus === 'Downtime') {
        Cache::put("mqtt_show_downtime_form_{$monitoringId}", true, 300);
      }

      // Signal frontend to update status
      Cache::put("mqtt_status_signal_{$monitoringId}", [
        'status' => $normalizedStatus,
        'timestamp' => now()->toIso8601String()
      ], 60);

      $this->info("✓ Status updated for monitoring {$monitoringId}: {$normalizedStatus}");
      Log::info("MQTT Status: Monitoring {$monitoringId}, Status: {$normalizedStatus}");
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

      // Signal frontend to show NG form with qty from MQTT
      Cache::put("mqtt_ng_signal_{$monitoringId}", [
        'show' => true,
        'qty' => $qty,
        'timestamp' => now()->toIso8601String()
      ], 300);

      $this->info("✓ NG signal received for monitoring {$monitoringId}: qty {$qty}");
      Log::info("MQTT NG: Monitoring {$monitoringId}, Qty: {$qty}");
    } catch (\Exception $e) {
      Log::error('Error handling NG: ' . $e->getMessage());
      $this->error("ERROR: " . $e->getMessage());
    }
  }
}
