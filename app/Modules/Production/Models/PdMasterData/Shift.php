<?php

namespace App\Models\PdMasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
  use HasFactory;

  protected $table = 'm_shifts';
  protected $primaryKey = 'shift_id';

  protected $fillable = [
    'shift_name',
    'start_time',
    'end_time',
    'break_duration'
  ];
}
