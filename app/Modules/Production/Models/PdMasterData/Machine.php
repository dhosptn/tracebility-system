<?php

namespace App\Modules\Production\Models\PdMasterData;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
  use \Illuminate\Database\Eloquent\SoftDeletes;

  protected $table = 'm_machine';

  protected $fillable = [
    'machine_code',
    'machine_name',
    'status',
    'location',
    'description'
  ];
}
