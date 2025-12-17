<?php

require_once 'vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// MQTT Configuration
$host = '127.0.0.1';
$port = 1883;
$clientId = 'test_realtime_' . uniqid();

// Test data
$machineCode = 'MC001'; // Ganti dengan machine_code yang ada di database
$topic = "production/{$machineCode}/signal";

echo "=== MQTT Real-time Test ===\n";
echo "Host: {$host}:{$port}\n";
echo "Topic: {$topic}\n";
echo "Machine: {$machineCode}\n";
echo "\n";

try {
  // Create MQTT client
  $client = new MqttClient($host, $port, $clientId);

  // Connection settings
  $connectionSettings = (new ConnectionSettings)
    ->setKeepAliveInterval(60)
    ->setConnectTimeout(10)
    ->setUseTls(false);

  // Connect
  echo "Connecting to MQTT broker...\n";
  $client->connect($connectionSettings, true);
  echo "✓ Connected successfully!\n\n";

  // Test sequence for real-time updates
  $testSequence = [
    [
      'name' => 'Set Status to Running',
      'payload' => [
        'trx_type' => 'status',
        'mesin' => $machineCode,
        'status' => 'Running',
        'time' => date('H:i:s')
      ]
    ],
    [
      'name' => 'Add 5 OK products',
      'payload' => [
        'trx_type' => 'qty_ok',
        'mesin' => $machineCode,
        'qty' => 5,
        'time' => date('H:i:s')
      ]
    ],
    [
      'name' => 'Report NG with modal',
      'payload' => [
        'trx_type' => 'ng',
        'mesin' => $machineCode,
        'qty' => 2,
        'ng_type' => 'Scratch',
        'ng_reason' => 'Surface defect detected',
        'time' => date('H:i:s')
      ]
    ],
    [
      'name' => 'Set Status to Downtime',
      'payload' => [
        'trx_type' => 'status',
        'mesin' => $machineCode,
        'status' => 'Downtime',
        'time' => date('H:i:s')
      ]
    ],
    [
      'name' => 'Report Downtime with modal',
      'payload' => [
        'trx_type' => 'downtime',
        'mesin' => $machineCode,
        'downtime_type' => 'Machine Breakdown',
        'downtime_reason' => 'Motor overheating',
        'time' => date('H:i:s')
      ]
    ],
    [
      'name' => 'Set Status back to Running',
      'payload' => [
        'trx_type' => 'status',
        'mesin' => $machineCode,
        'status' => 'Running',
        'time' => date('H:i:s')
      ]
    ],
    [
      'name' => 'Continue production - add 3 OK',
      'payload' => [
        'trx_type' => 'qty_ok',
        'mesin' => $machineCode,
        'qty' => 3,
        'time' => date('H:i:s')
      ]
    ]
  ];

  foreach ($testSequence as $index => $test) {
    echo "Step " . ($index + 1) . ": {$test['name']}\n";
    echo "Payload: " . json_encode($test['payload'], JSON_PRETTY_PRINT) . "\n";

    $client->publish($topic, json_encode($test['payload']), 0);
    echo "✓ Signal sent!\n";

    // Wait between signals
    echo "Waiting 3 seconds...\n\n";
    sleep(3);
  }

  // Disconnect
  $client->disconnect();
  echo "✓ Disconnected from MQTT broker\n";

  echo "\n=== Real-time Test Completed ===\n";
  echo "Expected behavior:\n";
  echo "1. Status changes should reset Current Time to 00:00:00\n";
  echo "2. QTY updates should appear immediately without refresh\n";
  echo "3. NG signal should show modal with pre-filled data\n";
  echo "4. Downtime signal should show modal with pre-filled data\n";
  echo "5. Timeline should update from t_production_status_log\n";
  echo "6. OEE metrics should update in real-time\n";
  echo "\nMake sure 'php artisan mqtt:production-listener' is running!\n";
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  echo "\nTroubleshooting:\n";
  echo "1. Make sure Mosquitto MQTT broker is running\n";
  echo "2. Check if port 1883 is accessible\n";
  echo "3. Verify MQTT configuration in .env file\n";
  echo "4. Ensure MQTT listener is running: php artisan mqtt:production-listener\n";
}
