<?php

/**
 * Test Script for Unified MQTT Payload
 * 
 * Usage: php test-unified-mqtt.php
 */

require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// MQTT Configuration
$host = $_ENV['MQTT_HOST'] ?? '127.0.0.1';
$port = (int)($_ENV['MQTT_PORT'] ?? 1883);
$clientId = 'test_unified_mqtt_' . uniqid();

echo "===========================================\n";
echo "MQTT Unified Payload Test Script\n";
echo "===========================================\n";
echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Client ID: {$clientId}\n";
echo "\n";

try {
  // Create MQTT client
  $mqtt = new MqttClient($host, $port, $clientId);

  // Connection settings
  $connectionSettings = (new ConnectionSettings)
    ->setKeepAliveInterval(60)
    ->setConnectTimeout(3)
    ->setUseTls(false)
    ->setTlsSelfSignedAllowed(false);

  // Connect
  echo "Connecting to MQTT broker...\n";
  $mqtt->connect($connectionSettings, true);
  echo "✓ Connected successfully!\n\n";

  // Test data
  $machineCode = 'MC001'; // Ganti dengan machine_code yang ada di database
  $topic = "production/{$machineCode}/signal";

  // Test 1: Status Update
  echo "Test 1: Sending Status Update...\n";
  $statusPayload = json_encode([
    'trx_type' => 'status',
    'mesin' => $machineCode,
    'status' => 'Running',
    'time' => date('H:i:s')
  ]);
  echo "Topic: {$topic}\n";
  echo "Payload: {$statusPayload}\n";
  $mqtt->publish($topic, $statusPayload, 0);
  echo "✓ Status update sent!\n\n";
  sleep(1);

  // Test 2: Qty OK
  echo "Test 2: Sending Qty OK...\n";
  $qtyOkPayload = json_encode([
    'trx_type' => 'qty_ok',
    'mesin' => $machineCode,
    'qty' => 5,
    'time' => date('H:i:s')
  ]);
  echo "Topic: {$topic}\n";
  echo "Payload: {$qtyOkPayload}\n";
  $mqtt->publish($topic, $qtyOkPayload, 0);
  echo "✓ Qty OK sent!\n\n";
  sleep(1);

  // Test 3: NG Report
  echo "Test 3: Sending NG Report...\n";
  $ngPayload = json_encode([
    'trx_type' => 'ng',
    'mesin' => $machineCode,
    'qty' => 2,
    'ng_type' => 'Scratch',
    'ng_reason' => 'Surface defect from test',
    'time' => date('H:i:s')
  ]);
  echo "Topic: {$topic}\n";
  echo "Payload: {$ngPayload}\n";
  $mqtt->publish($topic, $ngPayload, 0);
  echo "✓ NG report sent!\n\n";
  sleep(1);

  // Test 4: Downtime Report
  echo "Test 4: Sending Downtime Report...\n";
  $downtimePayload = json_encode([
    'trx_type' => 'downtime',
    'mesin' => $machineCode,
    'downtime_type' => 'Breakdown',
    'downtime_reason' => 'Motor failure from test',
    'time' => date('H:i:s')
  ]);
  echo "Topic: {$topic}\n";
  echo "Payload: {$downtimePayload}\n";
  $mqtt->publish($topic, $downtimePayload, 0);
  echo "✓ Downtime report sent!\n\n";
  sleep(1);

  // Test 5: Multiple Qty OK (rapid fire)
  echo "Test 5: Sending Multiple Qty OK (rapid fire)...\n";
  for ($i = 1; $i <= 3; $i++) {
    $rapidPayload = json_encode([
      'trx_type' => 'qty_ok',
      'mesin' => $machineCode,
      'qty' => 1,
      'time' => date('H:i:s')
    ]);
    echo "  [{$i}] Sending qty_ok...\n";
    $mqtt->publish($topic, $rapidPayload, 0);
    usleep(500000); // 0.5 second delay
  }
  echo "✓ Multiple qty OK sent!\n\n";

  // Disconnect
  $mqtt->disconnect();
  echo "✓ Disconnected from MQTT broker\n\n";

  echo "===========================================\n";
  echo "All tests completed successfully!\n";
  echo "===========================================\n";
  echo "\n";
  echo "Next steps:\n";
  echo "1. Check MQTT listener output: php artisan mqtt:production-listener\n";
  echo "2. Check logs: tail -f storage/logs/laravel.log\n";
  echo "3. Check database for updated records\n";
  echo "\n";
} catch (\Exception $e) {
  echo "✗ Error: " . $e->getMessage() . "\n";
  echo "\n";
  echo "Troubleshooting:\n";
  echo "1. Make sure MQTT broker is running\n";
  echo "2. Check .env configuration (MQTT_HOST, MQTT_PORT)\n";
  echo "3. Verify machine code '{$machineCode}' exists in m_machine table\n";
  echo "4. Ensure there's an active monitoring for this machine\n";
  exit(1);
}
