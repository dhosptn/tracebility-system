<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class MqttService
{
  protected $client;
  protected $host;
  protected $port;
  protected $username;
  protected $password;
  protected $clientId;
  protected $lastError;

  public function __construct()
  {
    $this->host = env('MQTT_HOST', '127.0.0.1');
    $this->port = env('MQTT_PORT', 1883);

    $username = env('MQTT_USERNAME');
    $this->username = !empty($username) ? $username : null;

    $password = env('MQTT_PASSWORD');
    $this->password = !empty($password) ? $password : null;


    $clientId = env('MQTT_CLIENT_ID');
    $this->clientId = !empty($clientId) ? $clientId : 'laravel_' . uniqid();
  }

  public function connect()
  {
    try {
      $this->client = new MqttClient($this->host, $this->port, $this->clientId);

      $connectionSettings = (new ConnectionSettings)
        ->setUsername($this->username)
        ->setPassword($this->password)
        ->setKeepAliveInterval(60)
        ->setLastWillTopic('production/status')
        ->setLastWillMessage('client disconnected')
        ->setLastWillQualityOfService(1)
        ->setConnectTimeout(10)
        ->setUseTls(false);

      $this->client->connect($connectionSettings, true);

      Log::info('MQTT Connected successfully to ' . $this->host . ':' . $this->port);
      return true;
    } catch (\Exception $e) {
      $this->lastError = $e->getMessage();
      Log::error('MQTT Connection failed: ' . $e->getMessage() . ' (Host: ' . $this->host . ':' . $this->port . ')');
      return false;
    }
  }

  public function getLastError()
  {
    return $this->lastError;
  }

  public function publish($topic, $message, $qos = 0)
  {
    try {
      if (!$this->client) {
        $this->connect();
      }

      $this->client->publish($topic, json_encode($message), $qos);
      Log::info("MQTT Published to {$topic}: " . json_encode($message));
      return true;
    } catch (\Exception $e) {
      Log::error('MQTT Publish failed: ' . $e->getMessage());
      return false;
    }
  }

  public function subscribe($topic, $callback, $qos = 0)
  {
    try {
      if (!$this->client) {
        $this->connect();
      }

      $this->client->subscribe($topic, $callback, $qos);
      Log::info("MQTT Subscribed to {$topic}");
      return true;
    } catch (\Exception $e) {
      Log::error('MQTT Subscribe failed: ' . $e->getMessage());
      return false;
    }
  }

  public function loop($allowSleep = true)
  {
    try {
      if ($this->client) {
        $this->client->loop($allowSleep);
      }
    } catch (\Exception $e) {
      Log::error('MQTT Loop error: ' . $e->getMessage());
    }
  }

  public function disconnect()
  {
    try {
      if ($this->client) {
        $this->client->disconnect();
        Log::info('MQTT Disconnected');
      }
    } catch (\Exception $e) {
      Log::error('MQTT Disconnect failed: ' . $e->getMessage());
    }
  }
}
