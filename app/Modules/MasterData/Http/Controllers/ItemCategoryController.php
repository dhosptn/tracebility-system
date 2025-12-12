<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Modules\MasterData\Models\ItemCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ItemCategoryController extends Controller
{
  public function index()
  {
    return view('MasterData::itemmaster.itemcategory.index');
  }

  public function data()
  {
    $query = ItemCategory::where('is_delete', 'N');

    return DataTables::of($query)
      ->addIndexColumn()
      ->editColumn('transaction_status', function ($row) {
        return $row->transaction_status == 1 ? 'Inventory' : 'Non-Inventory';
      })
      ->addColumn('action', function ($row) {
        return '
<button class="btn btn-sm btn-warning edit" data-id="' . $row->item_cat_id . '">Edit</button>
<button class="btn btn-sm btn-danger delete" data-id="' . $row->item_cat_id . '">Delete</button>
';
      })
      ->make(true);
  }

  public function store(Request $request)
  {
    $request->validate([
      'item_cat_name' => 'required',
      'item_cat_type' => 'required', // dropdown Inventory / Non-Inventory
    ]);

    ItemCategory::create([
      'item_cat_name' => $request->item_cat_name,
      'item_cat_desc' => $request->item_cat_desc,
      'transaction_status' => $request->item_cat_type == 'Inventory' ? 1 : 2,
      'input_by' => auth()->user()->name ?? 'SYSTEM',
      'input_date' => Carbon::now(),
      'is_delete' => 'N'
    ]);

    return response()->json(['success' => true]);
  }
}
