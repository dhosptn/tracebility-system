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
        Schema::create('t_production_pending_signals', function (Blueprint $table) {
            $table->id('id');
            $table->string('machine_code');
            $table->string('trx_type');
            $table->json('payload');
            $table->dateTime('execute_at');
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_production_pending_signals');
    }
};
