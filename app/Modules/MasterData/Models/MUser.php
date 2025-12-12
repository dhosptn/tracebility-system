<?php

namespace App\Modules\MasterData\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MUser extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'm_user';

  protected $fillable = [
    'name',
    'nik',
    'role',
  ];
}
