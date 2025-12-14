<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    // Create all tables only if they don't exist
    $tables = [
      'm_user' => function (Blueprint $table) {
        $table->id();
        $table->string('nik', 50)->unique();
        $table->string('name', 100);
        $table->string('role', 50)->nullable();
        $table->timestamps();
        $table->softDeletes();
      },
      'm_machine' => function (Blueprint $table) {
        $table->id();
        $table->string('machine_code', 50)->unique();
        $table->string('machine_name', 100);
        $table->string('status', 20)->default('active');
        $table->string('location', 100)->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
        $table->softDeletes();
      },
      'm_shifts' => function (Blueprint $table) {
        $table->id('shift_id');
        $table->string('shift_name', 50);
        $table->time('start_time');
        $table->time('end_time');
        $table->integer('break_duration')->default(0);
        $table->timestamps();
      },
      'm_process' => function (Blueprint $table) {
        $table->id('proces_id');
        $table->string('process_name', 100);
        $table->text('process_desc')->nullable();
        $table->string('input_by', 100)->nullable();
        $table->timestamp('input_time')->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('edit_date')->nullable();
        $table->char('is_delete', 1)->default('N');
      },
      'm_routing' => function (Blueprint $table) {
        $table->id('routing_id');
        $table->string('routing_name', 100);
        $table->string('part_no', 50);
        $table->string('part_name', 100);
        $table->text('part_desc')->nullable();
        $table->text('routing_rmk')->nullable();
        $table->date('routing_active_date')->nullable();
        $table->tinyInteger('routing_status')->default(1);
        $table->string('input_by', 100)->nullable();
        $table->timestamp('input_date')->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('edit_date')->nullable();
        $table->char('is_delete', 1)->default('N');
      },
    ];

    foreach ($tables as $tableName => $callback) {
      if (!Schema::hasTable($tableName)) {
        Schema::create($tableName, $callback);
        echo "Created table: $tableName\n";
      }
    }

    // Tables with foreign keys
    if (!Schema::hasTable('m_routing_detail')) {
      Schema::create('m_routing_detail', function (Blueprint $table) {
        $table->id('routing_dtl_id');
        $table->unsignedBigInteger('routing_id');
        $table->unsignedBigInteger('process_id');
        $table->string('process_name', 100);
        $table->text('process_desc')->nullable();
        $table->integer('cycle_time_second')->default(0);
        $table->integer('urutan_proses')->default(0);
      });
    }

    if (!Schema::hasTable('m_bom')) {
      Schema::create('m_bom', function (Blueprint $table) {
        $table->id('bom_id');
        $table->string('bom_no', 50)->unique();
        $table->string('bom_name', 100);
        $table->string('part_no', 50);
        $table->string('part_name', 100);
        $table->text('part_desc')->nullable();
        $table->text('bom_rmk')->nullable();
        $table->date('bom_active_date')->nullable();
        $table->tinyInteger('bom_status')->default(1);
        $table->string('input_by', 100)->nullable();
        $table->timestamp('input_date')->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('edit_date')->nullable();
        $table->char('is_delete', 1)->default('N');
      });
    }

    if (!Schema::hasTable('m_bom_detail')) {
      Schema::create('m_bom_detail', function (Blueprint $table) {
        $table->id('bom_dtl_id');
        $table->unsignedBigInteger('bom_id');
        $table->string('part_no', 50);
        $table->string('part_name', 100);
        $table->text('part_desc')->nullable();
        $table->string('bom_dtl_qty', 20);
        $table->string('uom', 20)->nullable();
      });
    }

    if (!Schema::hasTable('t_lot')) {
      Schema::create('t_lot', function (Blueprint $table) {
        $table->id('lot_id');
        $table->string('lot_no', 50)->unique();
        $table->date('lot_date');
        $table->integer('qty_per_lot')->default(0);
        $table->text('lot_rmk')->nullable();
        $table->string('input_by', 100)->nullable();
        $table->timestamp('input_time')->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('edit_time')->nullable();
        $table->char('is_delete', 1)->default('N');
      });
    }

    if (!Schema::hasTable('t_wo')) {
      Schema::create('t_wo', function (Blueprint $table) {
        $table->id('wo_id');
        $table->string('wo_no', 50)->unique();
        $table->date('wo_date');
        $table->date('prod_date');
        $table->string('part_no', 50);
        $table->string('part_name', 100);
        $table->unsignedBigInteger('uom_id')->nullable();
        $table->integer('wo_qty')->default(0);
        $table->integer('ok_qty')->default(0);
        $table->integer('ng_qty')->default(0);
        $table->text('wo_rmk')->nullable();
        $table->string('wo_status', 20)->default('Draft');
        $table->unsignedBigInteger('lot_id')->nullable();
        $table->string('input_by', 100)->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('input_time')->nullable();
        $table->timestamp('edit_time')->nullable();
        $table->char('is_delete', 1)->default('N');
        $table->index('wo_no');
      });
    }

    if (!Schema::hasTable('t_wo_detail')) {
      Schema::create('t_wo_detail', function (Blueprint $table) {
        $table->id('wo_dtl_id');
        $table->string('wo_no', 50);
        $table->string('item_id', 50);
        $table->string('item_name', 100);
        $table->text('item_desc')->nullable();
        $table->decimal('wo_qty', 15, 2)->default(0);
        $table->decimal('bom_qty', 15, 2)->default(0);
        $table->index('wo_no');
      });
    }

    if (!Schema::hasTable('t_wo_transaction')) {
      Schema::create('t_wo_transaction', function (Blueprint $table) {
        $table->id('trx_id');
        $table->string('trx_no', 50)->unique();
        $table->date('trx_date');
        $table->unsignedBigInteger('wo_id');
        $table->string('wo_no', 50);
        $table->unsignedBigInteger('process_id');
        $table->string('process_name', 100);
        $table->integer('cycle_time')->default(0);
        $table->string('supervisor', 100)->nullable();
        $table->string('operator', 100)->nullable();
        $table->unsignedBigInteger('machine_id')->nullable();
        $table->unsignedBigInteger('shift_id')->nullable();
        $table->timestamp('start_time')->nullable();
        $table->timestamp('end_time')->nullable();
        $table->integer('target_qty')->default(0);
        $table->integer('actual_qty')->default(0);
        $table->integer('ok_qty')->default(0);
        $table->integer('ng_qty')->default(0);
        $table->string('status', 20)->default('Draft');
        $table->text('notes')->nullable();
        $table->string('input_by', 100)->nullable();
        $table->timestamp('input_time')->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('edit_time')->nullable();
        $table->char('is_delete', 1)->default('N');
        $table->index('wo_no');
      });
    }

    if (!Schema::hasTable('t_wo_completion')) {
      Schema::create('t_wo_completion', function (Blueprint $table) {
        $table->id('completion_id');
        $table->unsignedBigInteger('wo_id');
        $table->string('wo_no', 50);
        $table->timestamp('start_time')->nullable();
        $table->timestamp('end_time')->nullable();
        $table->string('status', 20)->default('In Progress');
        $table->string('input_by', 100)->nullable();
        $table->timestamp('input_time')->nullable();
        $table->string('edit_by', 100)->nullable();
        $table->timestamp('edit_time')->nullable();
        $table->char('is_delete', 1)->default('N');
        $table->index('wo_id');
        $table->index('wo_no');
      });
    }

    if (!Schema::hasTable('t_wo_completion_detail')) {
      Schema::create('t_wo_completion_detail', function (Blueprint $table) {
        $table->id('completion_detail_id');
        $table->unsignedBigInteger('completion_id');
        $table->unsignedBigInteger('process_id');
        $table->string('process_name', 100);
        $table->integer('cycle_time')->default(0);
        $table->timestamp('start_time')->nullable();
        $table->timestamp('end_time')->nullable();
        $table->integer('qty_ok')->default(0);
        $table->integer('qty_ng')->default(0);
        $table->string('status', 20)->default('Pending');
        $table->string('operator', 100)->nullable();
        $table->unsignedBigInteger('machine_id')->nullable();
        $table->text('notes')->nullable();
        $table->index('completion_id');
      });
    }
  }

  public function down(): void
  {
    $tables = [
      't_wo_completion_detail',
      't_wo_completion',
      't_wo_transaction',
      't_wo_detail',
      't_wo',
      't_lot',
      'm_bom_detail',
      'm_bom',
      'm_routing_detail',
      'm_routing',
      'm_process',
      'm_shifts',
      'm_machine',
      'm_user',
    ];

    foreach ($tables as $table) {
      Schema::dropIfExists($table);
    }
  }
};
