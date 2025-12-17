<?php

require_once 'vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// MQTT Configuration
$host = '127.0.0.1';
$port = 1883;
$clientId = 'test_ng_direct_' . uniqid();

// Test data
$machineCode = 'MC001'; // Ganti dengan machine_code yang ada di database
$topic = "production/{$machineCode}/signal";

echo "=== MQTT NG Direct Test ===\n";
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

  // Test NG Signal
  $ngPayload = [
    'trx_type' => 'ng',
    'mesin' => $machineCode,
    'qty' => 3,
    'ng_type' => 'Scratch',
    'ng_reason' => 'Surface defect from MQTT test',
    'time' => date('H:i:s')
  ];

  echo "Sending NG signal...\n";
  echo "Payload: " . json_encode($ngPayload, JSON_PRETTY_PRINT) . "\n";

  $client->publish($topic, json_encode($ngPayload), 0);
  echo "✓ NG signal sent!\n\n";

  // Wait a bit
  sleep(2);

  // Test QTY OK Signal
  $qtyOkPayload = [
    'trx_type' => 'qty_ok',
    'mesin' => $machineCode,
    'qty' => 5,
    'time' => date('H:i:s')
  ];

  echo "Sending QTY OK signal...\n";
  echo "Payload: " . json_encode($qtyOkPayload, JSON_PRETTY_PRINT) . "\n";

  $client->publish($topic, json_encode($qtyOkPayload), 0);
  echo "✓ QTY OK signal sent!\n\n";

  // Disconnect
  $client->disconnect();
  echo "✓ Disconnected from MQTT broker\n";

  echo "\n=== Test completed ===\n";
  echo "Check your Laravel logs and database to see if the signals were processed.\n";
  echo "Make sure 'php artisan mqtt:production-listener' is running!\n";
} catch (Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  echo "\nTroubleshooting:\n";
  echo "1. Make sure Mosquitto MQTT broker is running\n";
  echo "2. Check if port 1883 is accessible\n";
  echo "3. Verify MQTT configuration in .env file\n";
}
