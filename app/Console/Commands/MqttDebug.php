<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MqttDebug extends Command
{
  protected $signature = 'mqtt:debug {action : test|logs|status}';
  protected $description = 'Debug MQTT connection and issues';

  public function handle()
  {
    $action = $this->argument('action');

    switch ($action) {
      case 'test':
        $this->testConnection();
        break;
      case 'logs':
        $this->showLogs();
        break;
      case 'status':
        $this->showStatus();
        break;
      default:
        $this->error('Unknown action: ' . $action);
        $this->line('Available actions: test, logs, status');
    }
  }

  protected function testConnection()
  {
    $this->info('Testing MQTT Connection...');
    $this->line('');

    $host = env('MQTT_HOST', 'localhost');
    $port = env('MQTT_PORT', 1883);

    $this->info('Configuration:');
    $this->line("  Host: {$host}");
    $this->line("  Port: {$port}");
    $this->line("  Username: " . (env('MQTT_USERNAME') ? env('MQTT_USERNAME') : 'none'));
    $this->line('');

    // Test socket connection
    $this->info('Testing socket connection...');
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);

    if ($socket) {
      $this->info('✓ Socket connection successful');
      fclose($socket);
    } else {
      $this->error('✗ Socket connection failed');
      $this->error("  Error: {$errstr} (Code: {$errno})");
      $this->line('');
      $this->error('Troubleshooting:');
      $this->line('1. Check if MQTT broker is running:');
      $this->line('   docker ps | grep mosquitto');
      $this->line('   OR');
      $this->line('   net start mosquitto');
      $this->line('');
      $this->line('2. Check firewall settings');
      $this->line('');
      $this->line('3. Start MQTT with Docker:');
      $this->line('   docker-compose up -d mosquitto');
      return;
    }

    // Test MQTT connection
    $this->info('Testing MQTT protocol...');
    try {
      $mqttService = new \App\Services\MqttService();
      if ($mqttService->connect()) {
        $this->info('✓ MQTT connection successful');
        $this->line('');
        $this->info('Ready to run listener:');
        $this->line('  php artisan mqtt:production-listener');
      } else {
        $this->error('✗ MQTT connection failed');
      }
    } catch (\Exception $e) {
      $this->error('✗ Error: ' . $e->getMessage());
    }
  }

  protected function showLogs()
  {
    $this->info('Recent MQTT Logs:');
    $this->line('');

    $logFile = storage_path('logs/laravel.log');

    if (!file_exists($logFile)) {
      $this->warn('Log file not found');
      return;
    }

    $lines = file($logFile);
    $mqttLines = array_filter($lines, function ($line) {
      return stripos($line, 'mqtt') !== false;
    });

    if (empty($mqttLines)) {
      $this->warn('No MQTT logs found');
      return;
    }

    // Show last 20 MQTT logs
    $recent = array_slice($mqttLines, -20);
    foreach ($recent as $line) {
      $this->line(trim($line));
    }
  }

  protected function showStatus()
  {
    $this->info('MQTT Status:');
    $this->line('');

    $host = env('MQTT_HOST', 'localhost');
    $port = env('MQTT_PORT', 1883);

    // Check socket
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($socket) {
      $this->info('✓ MQTT Broker: Running');
      fclose($socket);
    } else {
      $this->error('✗ MQTT Broker: Not running');
    }

    // Check listener process
    $this->info('');
    $this->info('Listener Process:');

    if (PHP_OS_FAMILY === 'Windows') {
      $output = shell_exec('tasklist | findstr artisan');
      if ($output && stripos($output, 'mqtt') !== false) {
        $this->info('✓ Listener: Running');
      } else {
        $this->warn('⚠ Listener: Not running');
      }
    } else {
      $output = shell_exec('ps aux | grep "mqtt:production-listener"');
      if ($output && stripos($output, 'mqtt:production-listener') !== false) {
        $this->info('✓ Listener: Running');
      } else {
        $this->warn('⚠ Listener: Not running');
      }
    }

    $this->line('');
    $this->info('To start listener:');
    $this->line('  php artisan mqtt:production-listener');
  }
}
