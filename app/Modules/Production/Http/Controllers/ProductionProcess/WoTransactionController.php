<?php

namespace App\Modules\Production\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\WoTransaction;
use Illuminate\Http\Request;

class WoTransactionController extends Controller
{
    public function index()
    {
        return view('Production::wotransaction.index');
    }

    public function store(Request $request)
    {
        WoTransaction::create($request->all());
        return back()->with('success', 'WO Transaction created');
    }
}
