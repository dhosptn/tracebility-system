<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;

class MqttDebugListener extends Command
{
  protected $signature = 'mqtt:debug-listener';
  protected $description = 'Debug MQTT listener - shows all messages received';

  protected $mqttService;

  public function __construct()
  {
    parent::__construct();
    $this->mqttService = new MqttService();
  }

  public function handle()
  {
    $this->info('Starting MQTT Debug Listener...');
    $this->info('Configuration:');
    $this->line('  Host: ' . env('MQTT_HOST', '127.0.0.1'));
    $this->line('  Port: ' . env('MQTT_PORT', 1883));
    $this->line('');

    if (!$this->mqttService->connect()) {
      $this->error('Failed to connect to MQTT broker');
      $this->error('Reason: ' . $this->mqttService->getLastError());
      return 1;
    }

    $this->info('✓ Connected to MQTT broker');
    $this->line('');

    // Subscribe to ALL topics
    $this->mqttService->subscribe('#', [$this, 'handleAllMessages']);

    $this->info('Listening for ALL MQTT messages... (Press Ctrl+C to stop)');
    $this->line('');

    try {
      while (true) {
        $this->mqttService->loop(false);
        usleep(100000); // 100ms delay
      }
    } catch (\Exception $e) {
      $this->error('Error in listener loop: ' . $e->getMessage());
      Log::error('MQTT Debug Listener error: ' . $e->getMessage());
      return 1;
    }
  }

  public function handleAllMessages($topic, $message)
  {
    $this->line('');
    $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    $this->line('Topic: ' . $topic);
    $this->line('Message: ' . $message);
    $this->line('Timestamp: ' . now()->format('Y-m-d H:i:s.u'));

    // Try to parse JSON
    $data = json_decode($message, true);
    if ($data) {
      $this->line('Parsed JSON:');
      foreach ($data as $key => $value) {
        $this->line('  ' . $key . ': ' . $value);
      }
    }

    $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  }
}
