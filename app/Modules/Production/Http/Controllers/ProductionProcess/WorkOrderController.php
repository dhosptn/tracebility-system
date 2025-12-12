<?php

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\WorkOrder;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function index()
    {
        return view('Production::workorder.index');
    }

    public function store(Request $request)
    {
        WorkOrder::create($request->all());
        return back()->with('success', 'Work Order created');
    }
}
