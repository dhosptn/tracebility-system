<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class MasterProcess extends Model
{
  protected $table = 'm_process';
  protected $primaryKey = 'proces_id';
  public $timestamps = false;

  protected $fillable = [
    'process_name',
    'process_desc',
    'input_by',
    'input_time',
    'edit_by',
    'edit_date',
    'is_delete'
  ];
}
