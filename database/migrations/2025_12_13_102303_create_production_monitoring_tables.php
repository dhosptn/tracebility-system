<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Production Monitoring Main Table
        Schema::create('t_production_monitoring', function (Blueprint $table) {
            $table->id('monitoring_id');
            $table->string('wo_no', 50);
            $table->integer('wo_qty');
            $table->integer('process_id');
            $table->string('process_name', 100);
            $table->integer('cycle_time')->comment('in seconds');
            $table->string('supervisor', 100);
            $table->string('operator', 100);
            $table->unsignedBigInteger('machine_id');
            $table->unsignedBigInteger('shift_id');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('current_status', 20)->default('Ready');
            $table->integer('qty_ok')->default(0);
            $table->integer('qty_ng')->default(0);
            $table->integer('qty_actual')->default(0);
            $table->boolean('is_active')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->index('wo_no');
            $table->index('machine_id');
            $table->index('shift_id');
        });

        // Production Status Log Table
        Schema::create('t_production_status_log', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('monitoring_id');
            $table->string('status', 20);
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('monitoring_id')->references('monitoring_id')->on('t_production_monitoring')->onDelete('cascade');
        });

        // Production Downtime Table
        Schema::create('t_production_downtime', function (Blueprint $table) {
            $table->id('downtime_id');
            $table->unsignedBigInteger('monitoring_id');
            $table->string('downtime_type', 50);
            $table->string('downtime_reason', 200);
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('monitoring_id')->references('monitoring_id')->on('t_production_monitoring')->onDelete('cascade');
        });

        // Production NG Table
        Schema::create('t_production_ng', function (Blueprint $table) {
            $table->id('ng_id');
            $table->unsignedBigInteger('monitoring_id');
            $table->string('ng_type', 50);
            $table->string('ng_reason', 200);
            $table->integer('qty');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('monitoring_id')->references('monitoring_id')->on('t_production_monitoring')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_production_ng');
        Schema::dropIfExists('t_production_downtime');
        Schema::dropIfExists('t_production_status_log');
        Schema::dropIfExists('t_production_monitoring');
    }
};
