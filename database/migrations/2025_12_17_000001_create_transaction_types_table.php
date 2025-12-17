<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Master Transaction Types Table
    Schema::create('m_transaction_types', function (Blueprint $table) {
      $table->id('trx_type_id');
      $table->string('trx_type_code', 50)->unique()->comment('e.g., status, qty_ok, ng, downtime');
      $table->string('trx_type_name', 100)->comment('e.g., Status Update, Quantity OK, NG Report');
      $table->text('trx_type_desc')->nullable();
      $table->boolean('is_active')->default(1);
      $table->string('input_by', 100)->nullable();
      $table->timestamp('input_date')->nullable();
      $table->string('edit_by', 100)->nullable();
      $table->timestamp('edit_date')->nullable();
      $table->timestamps();
    });

    // Insert default transaction types
    DB::table('m_transaction_types')->insert([
      [
        'trx_type_code' => 'status',
        'trx_type_name' => 'Status Update',
        'trx_type_desc' => 'Update status mesin (Ready, Running, Downtime, Stop)',
        'is_active' => 1,
        'input_by' => 'SYSTEM',
        'input_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'trx_type_code' => 'qty_ok',
        'trx_type_name' => 'Quantity OK',
        'trx_type_desc' => 'Pencatatan produk OK',
        'is_active' => 1,
        'input_by' => 'SYSTEM',
        'input_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'trx_type_code' => 'ng',
        'trx_type_name' => 'NG Report',
        'trx_type_desc' => 'Pencatatan produk NG/reject',
        'is_active' => 1,
        'input_by' => 'SYSTEM',
        'input_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'trx_type_code' => 'downtime',
        'trx_type_name' => 'Downtime Report',
        'trx_type_desc' => 'Pencatatan downtime mesin',
        'is_active' => 1,
        'input_by' => 'SYSTEM',
        'input_date' => now(),
        'created_at' => now(),
        'updated_at' => now(),
      ],
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('m_transaction_types');
  }
};
