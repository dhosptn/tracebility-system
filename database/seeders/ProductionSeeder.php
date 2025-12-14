<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if monitoring record 1 exists
        $exists = DB::table('t_production_monitoring')->where('monitoring_id', 1)->exists();
        
        if (!$exists) {
            DB::table('t_production_monitoring')->insert([
                'monitoring_id' => 1,
                'wo_no' => 'WO-2023-001',
                'wo_qty' => 1000,
                'process_id' => 1,
                'process_name' => 'Assembly',
                'cycle_time' => 60,
                'supervisor' => 'Supervisor A',
                'operator' => 'Operator B',
                'machine_id' => 1,
                'shift_id' => 1,
                'start_time' => Carbon::now(),
                'current_status' => 'Running',
                'qty_ok' => 0,
                'qty_ng' => 0,
                'qty_actual' => 0,
                'is_active' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            $this->command->info('Created dummy production monitoring record with ID 1');
        } else {
            $this->command->info('Production monitoring record with ID 1 already exists');
        }
    }
}
