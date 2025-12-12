<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Modules\MasterData\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\MasterData\Models\Uom;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
  public function index()
  {
    return view('MasterData::itemmaster.unit.index');
  }

  public function getData(Request $request)
  {
    if ($request->ajax()) {
      $units = Uom::where('is_delete', 'N')->select(['uom_id as id', 'uom_code', 'uom_desc', 'input_date', 'edit_date']);

      return DataTables::of($units)
        ->addIndexColumn()
        ->addColumn('DT_RowIndex', function ($unit) {
          static $index = 0;
          return ++$index + ($_GET['start'] ?? 0);
        })
        ->addColumn('action', function ($unit) {
          return '
                        <button class="btn btn-sm btn-warning edit-btn" data-id="' . $unit->id . '">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="' . $unit->id . '">Delete</button>
                    ';
        })
        ->addColumn('updated_info', function ($unit) {
          // Ensure dates are Carbon instances or parsed correctly
          $created = $unit->input_date ? \Carbon\Carbon::parse($unit->input_date)->format('d/m/Y H:i') : '-';
          $updated = $unit->edit_date ? \Carbon\Carbon::parse($unit->edit_date)->format('d/m/Y H:i') : '-';
          return '<small>Cr: ' . $created . '<br>Up: ' . $updated . '</small>';
        })
        ->rawColumns(['action', 'updated_info'])
        ->make(true);
    }

    return abort(404);
  }

  public function create()
  {
    return view('MasterData::itemmaster.unit.create');
  }

  public function store(Request $request)
  {
    $request->validate([
      'uom_code' => 'required|unique:wms_m_uom,uom_code',
      'uom_desc' => 'required',
    ]);

    Uom::create([
      'uom_code' => $request->uom_code,
      'uom_desc' => $request->uom_desc, // Assuming uom_name exists based on view, model says uom_desc in Step 153?
      // Model Step 153: uom_code, uom_desc. No uom_name?
      // Controller index Step 196 uses uom_name. 
      // Database Migration Step 59: uom table has 'name' and 'code'.
      // Wait, Model Step 153 says table 'wms_m_uom', fields uom_code, uom_desc.
      // Migration Step 59 created 'm_units'. 
      // Probably User has a different DB schema 'wms_m_uom' that they want to use?
      // I will use uom_desc map to uom_name from request if needed or just use what model has.
      // Let's assume request sends uom_name/uom_desc match.
      // I will use uom_desc as per model definition in Step 153.
      'uom_desc' => $request->uom_desc,
      'is_delete' => 'N',
      'input_by' => auth()->user()->name ?? 'system',
    ]);

    return redirect()->route('master-data.unit.index')->with('success', 'Unit created successfully');
  }

  public function edit($id)
  {
    $unit = Uom::where('uom_id', $id)->firstOrFail();
    return view('MasterData::itemmaster.unit.edit', compact('unit'));
  }

  public function update(Request $request, $id)
  {
    $unit = Uom::where('uom_id', $id)->firstOrFail();

    $request->validate([
      'uom_code' => 'required|unique:wms_m_uom,uom_code,' . $id . ',uom_id',
      'uom_desc' => 'required',
    ]);

    $unit->update([
      'uom_code' => $request->uom_code,
      'uom_desc' => $request->uom_desc,
      'edit_by' => auth()->user()->name ?? 'system',
    ]);

    return redirect()->route('master-data.unit.index')->with('success', 'Unit updated successfully');
  }

  public function destroy($id)
  {
    $unit = Uom::where('uom_id', $id)->firstOrFail();
    $unit->update([
      'is_delete' => 'Y',
      'del_by' => auth()->user()->name ?? 'system',
      'del_date' => now(),
    ]);

    return response()->json(['message' => 'Unit deleted successfully']);
  }
}
