<?php

namespace App\Modules\MasterData\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\MasterData\Http\Controllers\Controller;

class DashboardController extends Controller
{
  public function index()
  {
    return view('itemmaster::itemmaster.index');
  }
}
