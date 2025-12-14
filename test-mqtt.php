<?php

require 'vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

echo "=== MQTT Connection Test ===\n\n";

$host = '127.0.0.1';
$port = 1883;

echo "Connecting to {$host}:{$port}...\n";

try {
  $client = new MqttClient($host, $port, 'test_client_' . uniqid());

  $connectionSettings = (new ConnectionSettings)
    ->setKeepAliveInterval(60)
    ->setConnectTimeout(10)
    ->setUseTls(false);

  $client->connect($connectionSettings, true);
  echo "✓ Connected successfully!\n\n";

  // Subscribe to test topic
  echo "Subscribing to production/# ...\n";
  $client->subscribe('production/#', function ($topic, $message) {
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Topic: {$topic}\n";
    echo "Message: {$message}\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
  });

  echo "✓ Subscribed!\n";
  echo "\nListening for messages... (Press Ctrl+C to stop)\n";
  echo "In another terminal, run:\n";
  echo "  docker exec mosquitto mosquitto_pub -h localhost -t \"production/1/qty_ok\" -m '{\"monitoring_id\":1,\"qty\":1}'\n\n";

  // Listen for 60 seconds
  for ($i = 0; $i < 600; $i++) {
    $client->loop(true);
    usleep(100000); // 100ms
  }

  $client->disconnect();
  echo "\nDisconnected.\n";
} catch (\Exception $e) {
  echo "✗ Error: " . $e->getMessage() . "\n";
  exit(1);
}
