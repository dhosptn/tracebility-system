<?php

/**
 * Script untuk mengirim test signals ke MQTT
 * Jalankan dengan: php send-test-signals.php
 */

require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$monitoringId = 1;

// MQTT Configuration
$host = env('MQTT_HOST', '127.0.0.1');
$port = (int) env('MQTT_PORT', 1883);
$clientId = 'test-signal-sender-' . uniqid();

echo "Connecting to MQTT broker at {$host}:{$port}...\n";

try {
  $mqtt = new MqttClient($host, $port, $clientId);
  $connectionSettings = (new ConnectionSettings())
    ->setKeepAliveInterval(60)
    ->setLastWillTopic('test/status')
    ->setLastWillMessage('offline')
    ->setLastWillQualityOfService(0);

  $mqtt->connect($connectionSettings, true);
  echo "✓ Connected to MQTT broker\n\n";

  // 1. Send Running status
  echo "1. Sending Running status...\n";
  $payload = json_encode(['monitoring_id' => $monitoringId, 'status' => 'Run']);
  $mqtt->publish("production/{$monitoringId}/status", $payload, 0);
  echo "   ✓ Sent: {$payload}\n";
  echo "   Wait 5 seconds...\n\n";
  sleep(5);

  // 2. Send QTY OK signals (3x)
  for ($i = 1; $i <= 3; $i++) {
    echo "2.{$i}. Sending QTY OK signal #{$i}...\n";
    $payload = json_encode(['monitoring_id' => $monitoringId, 'qty' => 1]);
    $mqtt->publish("production/{$monitoringId}/qty_ok", $payload, 0);
    echo "   ✓ Sent: {$payload}\n";
    echo "   Wait 3 seconds...\n\n";
    sleep(3);
  }

  // 3. Send more QTY OK to see cycle time changes
  echo "3. Sending 5 more QTY OK signals to see cycle time variations...\n";
  for ($i = 1; $i <= 5; $i++) {
    $payload = json_encode(['monitoring_id' => $monitoringId, 'qty' => 1]);
    $mqtt->publish("production/{$monitoringId}/qty_ok", $payload, 0);
    echo "   ✓ Sent QTY OK #{$i}\n";
    sleep(2); // Different intervals to create cycle time variations
  }
  echo "\n";

  // 4. Send Stop status
  echo "4. Sending Stop status...\n";
  $payload = json_encode(['monitoring_id' => $monitoringId, 'status' => 'Stop']);
  $mqtt->publish("production/{$monitoringId}/status", $payload, 0);
  echo "   ✓ Sent: {$payload}\n";
  echo "   Wait 3 seconds...\n\n";
  sleep(3);

  // 5. Send Running again
  echo "5. Sending Running status again...\n";
  $payload = json_encode(['monitoring_id' => $monitoringId, 'status' => 'Run']);
  $mqtt->publish("production/{$monitoringId}/status", $payload, 0);
  echo "   ✓ Sent: {$payload}\n\n";

  $mqtt->disconnect();
  echo "✓ Disconnected from MQTT broker\n\n";

  echo "========================================\n";
  echo "Test signals sent successfully!\n";
  echo "========================================\n";
  echo "Now check your browser:\n";
  echo "1. Availability should be > 0 and increasing\n";
  echo "2. Performance should be > 0\n";
  echo "3. Quality should show correct percentage\n";
  echo "4. OEE should be > 0\n";
  echo "5. Cycle Times should show actual values (not 200)\n";
  echo "6. Machine Status should show 'RUN' (green)\n";
  echo "========================================\n";
} catch (\Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  echo "Make sure:\n";
  echo "1. MQTT broker is running (mosquitto)\n";
  echo "2. MQTT listener is running: php artisan mqtt:production-listener\n";
  echo "3. .env has correct MQTT_HOST and MQTT_PORT\n";
}
