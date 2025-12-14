<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MqttService;
use Illuminate\Support\Facades\Log;

class TestMqttConnection extends Command
{
  protected $signature = 'mqtt:test-connection';
  protected $description = 'Test MQTT broker connection';

  public function handle()
  {
    $this->info('Testing MQTT Connection...');
    $this->line('');

    // Get config
    $host = env('MQTT_HOST', 'localhost');
    $port = env('MQTT_PORT', 1883);
    $username = env('MQTT_USERNAME', '');
    $password = env('MQTT_PASSWORD', '');

    $this->info("MQTT Configuration:");
    $this->line("  Host: {$host}");
    $this->line("  Port: {$port}");
    $this->line("  Username: " . ($username ? $username : 'none'));
    $this->line("  Password: " . ($password ? '***' : 'none'));
    $this->line('');

    // Test connection
    $this->info('Attempting to connect...');

    try {
      $mqttService = new MqttService();

      if ($mqttService->connect()) {
        $this->info('✓ Connection successful!');
        $this->line('');

        // Test publish
        $this->info('Testing publish...');
        $testTopic = 'production/test';
        $testMessage = ['test' => 'message', 'timestamp' => now()->toIso8601String()];

        if ($mqttService->publish($testTopic, $testMessage)) {
          $this->info("✓ Published to {$testTopic}");
          $this->line('');
        }

        // Test subscribe
        $this->info('Testing subscribe (listening for 5 seconds)...');
        $this->line('Try publishing to production/test in another terminal:');
        $this->line('  mosquitto_pub -h ' . $host . ' -t "production/test" -m \'{"test":"data"}\'');
        $this->line('');

        $messageReceived = false;
        $startTime = time();
        $timeout = 5;

        $mqttService->subscribe('production/test', function ($topic, $message) use (&$messageReceived) {
          $this->info("✓ Received message on {$topic}:");
          $this->line("  Payload: {$message}");
          $messageReceived = true;
        });

        while ((time() - $startTime) < $timeout && !$messageReceived) {
          $mqttService->loop(true);
          usleep(100000);
        }

        if (!$messageReceived) {
          $this->warn('⚠ No message received (timeout)');
        }

        $mqttService->disconnect();
        $this->info('✓ Disconnected');
        $this->line('');
        $this->info('All tests passed!');

        return 0;
      } else {
        $this->error('✗ Connection failed');
        $this->line('');
        $this->error('Troubleshooting:');
        $this->line('1. Check if MQTT broker is running');
        $this->line('   Windows: net start mosquitto');
        $this->line('   Linux: sudo systemctl status mosquitto');
        $this->line('   Docker: docker ps | grep mosquitto');
        $this->line('');
        $this->line('2. Check if host and port are correct');
        $this->line("   Current: {$host}:{$port}");
        $this->line('');
        $this->line('3. Check firewall settings');
        $this->line('');
        $this->line('4. Test with mosquitto_pub:');
        $this->line("   mosquitto_pub -h {$host} -p {$port} -t test -m hello");
        $this->line('');

        return 1;
      }
    } catch (\Exception $e) {
      $this->error('✗ Error: ' . $e->getMessage());
      $this->line('');
      $this->error('Stack trace:');
      $this->line($e->getTraceAsString());

      return 1;
    }
  }
}
