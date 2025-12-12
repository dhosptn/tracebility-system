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
    Schema::create('m_item_categories', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code')->unique();
      $table->text('description')->nullable();
      $table->timestamps();
    });

    Schema::create('m_units', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code')->unique(); // e.g., PCS, KG, M
      $table->timestamps();
    });

    Schema::create('m_item_masters', function (Blueprint $table) {
      $table->id();
      $table->string('item_code')->unique();
      $table->string('item_name');
      $table->foreignId('category_id')->constrained('m_item_categories');
      $table->foreignId('unit_id')->constrained('m_units');
      $table->decimal('price', 15, 2)->default(0);
      $table->integer('stock')->default(0);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('m_item_masters');
    Schema::dropIfExists('m_units');
    Schema::dropIfExists('m_item_categories');
  }
};
