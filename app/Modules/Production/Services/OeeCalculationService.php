<?php

namespace App\Modules\Production\Services;

use App\Modules\Production\Models\ProductionProcess\ProductionMonitoring;
use App\Modules\Production\Models\ProductionProcess\ProductionStatusLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    // Calculate Planned Production Time (total time from all status logs)
    $plannedTime = self::calculatePlannedTime($monitoring);

    // Calculate Availability (Operating Time / Planned Time)
    $availability = $plannedTime > 0 ? ($operatingTime / $plannedTime * 100) : 0;
    $availability = min($availability, 100); // Cap at 100%

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
   * Calculate Planned Production Time (total time from all status logs)
   */
  private static function calculatePlannedTime($monitoring)
  {
    $plannedTime = 0;
    $now = now('Asia/Jakarta');

    foreach ($monitoring->statusLogs as $log) {
      if ($log->status === 'Finish') continue;
      
      if ($log->duration_seconds && $log->duration_seconds > 0) {
        $plannedTime += $log->duration_seconds;
      } else if ($log->end_time) {
        $startTime = \Carbon\Carbon::parse($log->start_time);
        $endTime = \Carbon\Carbon::parse($log->end_time);
        $duration = $startTime->diffInSeconds($endTime, false);
        if ($duration > 0) {
          $plannedTime += $duration;
        }
      } else {
        // Still ongoing - calculate from start to now
        $startTime = \Carbon\Carbon::parse($log->start_time);
        $duration = $startTime->diffInSeconds($now, false);
        if ($duration > 0) {
          $plannedTime += $duration;
        }
      }
    }

    return $plannedTime;
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
        if ($log->duration_seconds && $log->duration_seconds > 0) {
          $operatingTime += $log->duration_seconds;
        } else if ($log->end_time) {
          $startTime = \Carbon\Carbon::parse($log->start_time);
          $endTime = \Carbon\Carbon::parse($log->end_time);
          $duration = $startTime->diffInSeconds($endTime, false);
          if ($duration > 0) {
            $operatingTime += $duration;
          }
        } else {
          // Still running - calculate from start to now
          $startTime = \Carbon\Carbon::parse($log->start_time);
          $duration = $startTime->diffInSeconds($now, false);
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
   * Capped at 100% to avoid exceeding 100%
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

    $performance = ($expectedTime / $operatingTime * 100);

    // Cap at 100% - can't exceed 100%
    return min($performance, 100);
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
  public static function recordOkTimestamp($monitoringId, $timestamp = null)
  {
    $cacheKey = "ok_timestamps_{$monitoringId}";
    $effectiveTime = $timestamp instanceof \Carbon\Carbon ? $timestamp : now('Asia/Jakarta');
    
    $timestamps = Cache::get($cacheKey, []);
    $timestamps[] = $effectiveTime->toIso8601String();

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

      // Uptime = Availability (same calculation)
      // Both represent Operating Time / Planned Time
      $uptime = $metrics['availability'];

      $result = [
        'availability' => $metrics['availability'],
        'performance' => $metrics['performance'],
        'quality' => $metrics['quality'],
        'oee' => $metrics['oee'],
        'uptime' => $uptime,
        'avg_cycle_time' => round($metrics['avg_cycle_time'], 1),
        'last_cycle_time' => round($metrics['last_cycle_time'], 1),
        'high_cycle_time' => round($metrics['high_cycle_time'], 1),
        'low_cycle_time' => round($metrics['low_cycle_time'], 1),
      ];

      Log::info("OEE Service Result for monitoring {$monitoringId}", $result);

      return $result;
    } catch (\Exception $e) {
      Log::error("Error calculating OEE metrics for monitoring {$monitoringId}: " . $e->getMessage());
      Log::error($e->getTraceAsString());

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
