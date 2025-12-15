<?php

namespace App\Modules\Production\Services;

use App\Modules\Production\Models\ProductionProcess\ProductionMonitoring;
use App\Modules\Production\Models\ProductionProcess\ProductionStatusLog;
use Illuminate\Support\Facades\Cache;

class OeeCalculationService
{
  /**
   * Calculate all OEE metrics for a monitoring session
   */
  public static function calculateMetrics($monitoringId)
  {
    $monitoring = ProductionMonitoring::with([
      'statusLogs' => function ($q) {
        $q->orderBy('start_time', 'asc');
      }
    ])->findOrFail($monitoringId);

    // Calculate Operating Time (only Running status)
    $operatingTime = self::calculateOperatingTime($monitoring);

    // Calculate Planned Production Time (total time from start to now)
    $plannedTime = now('Asia/Jakarta')->diffInSeconds($monitoring->start_time);

    // Calculate Availability
    $availability = $plannedTime > 0 ? ($operatingTime / $plannedTime * 100) : 0;

    // Calculate Performance
    $performance = self::calculatePerformance($monitoring, $operatingTime);

    // Calculate Quality
    $quality = self::calculateQuality($monitoring);

    // Calculate OEE
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    // Calculate Actual Cycle Times
    $cycleTimes = self::calculateCycleTimes($monitoring);

    return [
      'availability' => round($availability, 1),
      'performance' => round($performance, 1),
      'quality' => round($quality, 1),
      'oee' => round($oee, 1),
      'operating_time' => $operatingTime,
      'planned_time' => $plannedTime,
      'avg_cycle_time' => $cycleTimes['average'],
      'last_cycle_time' => $cycleTimes['last'],
      'high_cycle_time' => $cycleTimes['high'],
      'low_cycle_time' => $cycleTimes['low'],
    ];
  }

  /**
   * Calculate Operating Time (only Running status)
   */
  private static function calculateOperatingTime($monitoring)
  {
    $operatingTime = 0;
    $now = now('Asia/Jakarta');

    foreach ($monitoring->statusLogs as $log) {
      if ($log->status === 'Running') {
        // Convert start_time to Asia/Jakarta timezone
        $startTime = \Carbon\Carbon::parse($log->start_time)->setTimezone('Asia/Jakarta');

        if ($log->duration_seconds && $log->duration_seconds > 0) {
          $operatingTime += $log->duration_seconds;
        } else if ($log->end_time) {
          $endTime = \Carbon\Carbon::parse($log->end_time)->setTimezone('Asia/Jakarta');
          $duration = $endTime->diffInSeconds($startTime, false);
          if ($duration > 0) {
            $operatingTime += $duration;
          }
        } else {
          // Still running - calculate from start to now
          $duration = $now->diffInSeconds($startTime, false);
          if ($duration > 0) {
            $operatingTime += $duration;
          }
        }
      }
    }

    return $operatingTime;
  }

  /**
   * Calculate Performance
   * Performance = (Ideal Cycle Time × (OK+NG)) / Operating Time
   */
  private static function calculatePerformance($monitoring, $operatingTime)
  {
    if ($operatingTime <= 0) {
      return 0;
    }

    $totalProduced = $monitoring->qty_ok + $monitoring->qty_ng;
    if ($totalProduced <= 0) {
      return 0;
    }

    $idealCycleTime = $monitoring->cycle_time;
    $expectedTime = $idealCycleTime * $totalProduced;

    return ($expectedTime / $operatingTime * 100);
  }

  /**
   * Calculate Quality
   * Quality = OK / (OK+NG)
   */
  private static function calculateQuality($monitoring)
  {
    $totalProduced = $monitoring->qty_ok + $monitoring->qty_ng;

    if ($totalProduced <= 0) {
      return 0;
    }

    return ($monitoring->qty_ok / $totalProduced * 100);
  }

  /**
   * Calculate Actual Cycle Times
   * Based on timestamp differences between OK events
   */
  private static function calculateCycleTimes($monitoring)
  {
    // Get OK timestamps from cache or database
    $okTimestamps = Cache::get("ok_timestamps_{$monitoring->monitoring_id}", []);

    if (empty($okTimestamps)) {
      // Fallback to standard cycle time
      return [
        'average' => $monitoring->cycle_time,
        'last' => $monitoring->cycle_time,
        'high' => $monitoring->cycle_time * 1.2,
        'low' => $monitoring->cycle_time * 0.8,
      ];
    }

    $cycleTimes = [];
    for ($i = 1; $i < count($okTimestamps); $i++) {
      $diff = strtotime($okTimestamps[$i]) - strtotime($okTimestamps[$i - 1]);
      if ($diff > 0) {
        $cycleTimes[] = $diff;
      }
    }

    if (empty($cycleTimes)) {
      return [
        'average' => $monitoring->cycle_time,
        'last' => $monitoring->cycle_time,
        'high' => $monitoring->cycle_time * 1.2,
        'low' => $monitoring->cycle_time * 0.8,
      ];
    }

    return [
      'average' => round(array_sum($cycleTimes) / count($cycleTimes), 1),
      'last' => end($cycleTimes),
      'high' => max($cycleTimes),
      'low' => min($cycleTimes),
    ];
  }

  /**
   * Record OK timestamp for cycle time calculation
   */
  public static function recordOkTimestamp($monitoringId)
  {
    $cacheKey = "ok_timestamps_{$monitoringId}";
    $timestamps = Cache::get($cacheKey, []);
    $timestamps[] = now('Asia/Jakarta')->toIso8601String();

    // Keep only last 100 timestamps to avoid memory issues
    if (count($timestamps) > 100) {
      $timestamps = array_slice($timestamps, -100);
    }

    Cache::put($cacheKey, $timestamps, 86400); // 24 hours
  }

  /**
   * Get realtime metrics for display
   */
  public static function getRealtimeMetrics($monitoringId)
  {
    try {
      $metrics = self::calculateMetrics($monitoringId);

      // Calculate Uptime (Operating Time / Planned Time)
      $uptime = $metrics['planned_time'] > 0 ? ($metrics['operating_time'] / $metrics['planned_time'] * 100) : 0;

      $result = [
        'availability' => $metrics['availability'],
        'performance' => $metrics['performance'],
        'quality' => $metrics['quality'],
        'oee' => $metrics['oee'],
        'uptime' => round($uptime, 1),
        'avg_cycle_time' => $metrics['avg_cycle_time'],
        'last_cycle_time' => $metrics['last_cycle_time'],
        'high_cycle_time' => $metrics['high_cycle_time'],
        'low_cycle_time' => $metrics['low_cycle_time'],
      ];

      \Log::info("OEE Service Result for monitoring {$monitoringId}", $result);

      return $result;
    } catch (\Exception $e) {
      \Log::error("Error calculating OEE metrics for monitoring {$monitoringId}: " . $e->getMessage());
      \Log::error($e->getTraceAsString());

      // Return default values on error
      return [
        'availability' => 0,
        'performance' => 0,
        'quality' => 0,
        'oee' => 0,
        'uptime' => 0,
        'avg_cycle_time' => 0,
        'last_cycle_time' => 0,
        'high_cycle_time' => 0,
        'low_cycle_time' => 0,
      ];
    }
  }
}
