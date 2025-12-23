<?php

namespace App\Modules\Production\Services;

use App\Modules\Production\Models\ProductionProcess\ProductionMonitoring;
use App\Modules\Production\Models\ProductionProcess\ProductionStatusLog;
use App\Modules\Production\Models\ProductionProcess\WoTransaction;
use App\Modules\Production\Models\ProductionProcess\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductionFinishService
{
    /**
     * Finish a production monitoring session and create a transaction record
     * 
     * @param int $monitoringId
     * @return bool
     */
    public static function finishMonitoring($monitoringId)
    {
        try {
            Log::info("ProductionFinishService: Finishing monitoring ID {$monitoringId}");
            
            $monitoring = ProductionMonitoring::findOrFail($monitoringId);
            
            // Check if already finished
            if ($monitoring->current_status === 'Finish') {
                return true;
            }

            DB::beginTransaction();

            $nowIndonesia = now('Asia/Jakarta');

            // 1. Close current status log
            $lastLog = ProductionStatusLog::where('monitoring_id', $monitoringId)
                ->whereNull('end_time')
                ->latest('start_time')
                ->first();

            if ($lastLog) {
                $lastLog->update([
                    'end_time' => $nowIndonesia,
                    'duration_seconds' => $nowIndonesia->diffInSeconds($lastLog->start_time)
                ]);
            }

            // 2. Create 'Finish' status log
            ProductionStatusLog::create([
                'monitoring_id' => $monitoringId,
                'status' => 'Finish',
                'start_time' => $nowIndonesia,
                'created_at' => $nowIndonesia
            ]);

            // 3. Update monitoring status and deactivate
            $monitoring->update([
                'current_status' => 'Finish',
                'is_active' => 0,
                'end_time' => $nowIndonesia,
                'updated_at' => $nowIndonesia
            ]);

            // 4. Create WoTransaction (Auto Save)
            self::createTransaction($monitoring);

            // 5. Signal frontend via Cache
            Cache::put("mqtt_status_signal_{$monitoringId}", [
                'show' => true,
                'status' => 'Finish',
                'timestamp' => $nowIndonesia->toIso8601String(),
                'final_duration' => $lastLog ? $lastLog->duration_seconds : 0
            ], 60);

            DB::commit();
            Log::info("ProductionFinishService: Successfully finished monitoring {$monitoringId}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ProductionFinishService Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a WoTransaction record from monitoring data
     */
    private static function createTransaction($monitoring)
    {
        // Check if transaction already exists for this WO and Process today
        $exists = WoTransaction::where('wo_no', $monitoring->wo_no)
            ->where('process_id', $monitoring->process_id)
            ->whereDate('trx_date', now()->toDateString())
            ->exists();

        if ($exists) {
            Log::info("ProductionFinishService: Transaction already exists for monitoring {$monitoring->monitoring_id}");
            return;
        }

        // Generate Transaction Number
        $today = date('Ymd');
        $lastTrx = WoTransaction::where('trx_no', 'like', 'WX' . $today . '%')
            ->orderBy('trx_no', 'desc')
            ->first();

        $newSequence = $lastTrx ? intval(substr($lastTrx->trx_no, -4)) + 1 : 1;
        $trxNo = 'WX' . $today . sprintf('%04d', $newSequence);

        // Get Work Order ID
        $wo = WorkOrder::where('wo_no', $monitoring->wo_no)->first();

        WoTransaction::create([
            'trx_no' => $trxNo,
            'trx_date' => now()->toDateString(),
            'wo_id' => $wo ? $wo->wo_id : null,
            'wo_no' => $monitoring->wo_no,
            'process_id' => $monitoring->process_id,
            'process_name' => $monitoring->process_name,
            'cycle_time' => $monitoring->cycle_time,
            'supervisor' => $monitoring->supervisor,
            'operator' => $monitoring->operator,
            'machine_id' => $monitoring->machine_id,
            'shift_id' => $monitoring->shift_id,
            'start_time' => $monitoring->start_time,
            'end_time' => now(),
            'target_qty' => $monitoring->wo_qty,
            'actual_qty' => $monitoring->qty_actual,
            'ok_qty' => $monitoring->qty_ok,
            'ng_qty' => $monitoring->qty_ng,
            'status' => 'Draft',
            'notes' => 'Auto-generated from TV Display Finish',
            'input_by' => 'System',
            'input_time' => now(),
            'is_delete' => 'N'
        ]);

        Log::info("ProductionFinishService: Created transaction {$trxNo}");
    }
}
