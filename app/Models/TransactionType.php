<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
  protected $table = 'm_transaction_types';
  protected $primaryKey = 'trx_type_id';

  protected $fillable = [
    'trx_type_code',
    'trx_type_name',
    'trx_type_desc',
    'is_active',
    'input_by',
    'input_date',
    'edit_by',
    'edit_date',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'input_date' => 'datetime',
    'edit_date' => 'datetime',
  ];

  /**
   * Get transaction type by code
   */
  public static function getByCode($code)
  {
    return self::where('trx_type_code', $code)
      ->where('is_active', 1)
      ->first();
  }

  /**
   * Get all active transaction types
   */
  public static function getActive()
  {
    return self::where('is_active', 1)->get();
  }
}
