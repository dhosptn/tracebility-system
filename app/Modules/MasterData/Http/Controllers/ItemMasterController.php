<?php

namespace App\Modules\MasterData\Http\Controllers;

use App\Http\Controllers\Controller as ControllersController;
use App\Modules\MasterData\Http\Controllers\Controller;
use App\Modules\MasterData\Models\ItemMaster;
use App\Modules\MasterData\Models\Uom;
use App\Modules\MasterData\Models\ItemCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ItemMasterExport;

class ItemMasterController extends  ControllersController
{
  public function index()
  {
    $categories = ItemCategory::where('is_delete', 'N')->get();
    return view('MasterData::itemmaster.itemmaster.index', compact('categories'));
  }

  public function getDataTable(Request $request)
  {
    $query = ItemMaster::with(['uom', 'secondUom', 'category'])
      ->where('is_delete', 'N');

    // Filter by stock type
    if ($request->has('stock_type') && $request->stock_type != '') {
      $query->where('stock_type', $request->stock_type);
    }

    // Filter by category
    if ($request->has('category_id') && $request->category_id != '') {
      $query->where('item_cat_id', $request->category_id);
    }

    // Filter exclude category by name
    if ($request->has('exclude_category_name') && $request->exclude_category_name != '') {
      $query->whereHas('category', function ($q) use ($request) {
        $q->where('item_cat_name', '!=', $request->exclude_category_name);
      });
    }

    return DataTables::of($query)
      ->addIndexColumn()
      ->addColumn('uom_code', function ($item) {
        return $item->uom ? $item->uom->uom_code : '-';
      })
      ->addColumn('category_name', function ($item) {
        return $item->category ? $item->category->item_cat_name : '-';
      })
      ->addColumn('action', function ($item) {
        return '
          <div class="btn-group">
            <a href="' . route('itemmaster.edit', $item->item_id) . '" class="btn btn-warning btn-sm" title="Edit">
              <i class="fas fa-edit"></i>
            </a>
            <button type="button" class="btn btn-danger btn-sm btn-delete" data-id="' . $item->item_id . '" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        ';
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function export(Request $request)
    {
        // Apply filters if any
        $query = ItemMaster::with(['uom', 'category'])
            ->where('is_delete', 'N');

        if ($request->has('stock_type') && $request->stock_type != '') {
            $query->where('stock_type', $request->stock_type);
        }

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('item_cat_id', $request->category_id);
        }

        $items = $query->get();

        // Generate filename with timestamp
        $filename = 'item_master_' . date('Ymd_His') . '.xlsx';

        // Use the Export class
        return Excel::download(new ItemMasterExport($items), $filename);
    }

    // Atau jika ingin menggunakan metode manual tanpa Export class:
    public function exportManual(Request $request)
    {
        // Apply filters if any
        $query = ItemMaster::with(['uom', 'category'])
            ->where('is_delete', 'N');

        if ($request->has('stock_type') && $request->stock_type != '') {
            $query->where('stock_type', $request->stock_type);
        }

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('item_cat_id', $request->category_id);
        }

        $items = $query->get();

        // Generate filename with timestamp
        $filename = 'item_master_' . date('Ymd_His') . '.csv';

        // Create CSV headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        // Create callback function for streaming
        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");
            
            // Add headers
            fputcsv($file, [
                'No',
                'SKU / Part No',
                'Part Name',
                'Description',
                'Model',
                'Stock Type',
                'UOM',
                'Category',
                'Standard Price',
                'Barcode',
                'Volume (m³)',
                'Status',
                'Created At'
            ]);

            // Add data rows
            $no = 1;
            foreach ($items as $item) {
                fputcsv($file, [
                    $no++,
                    $item->item_number,
                    $item->item_name,
                    $item->item_description ?? '-',
                    $item->model ?? '-',
                    $item->stock_type == 'inventory' ? 'Inventory Item' : 'Non-Inventory Item',
                    $item->uom ? $item->uom->uom_code : '-',
                    $item->category ? $item->category->item_cat_name : '-',
                    'Rp ' . number_format($item->standard_price, 0, ',', '.'),
                    $item->barcode ?? '-',
                    $item->volume_m3 ?? '-',
                    $item->item_status,
                    $item->input_date
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

  public function create()
  {
    $uoms = Uom::where('is_delete', 'N')->get();
    $categories = ItemCategory::where('is_delete', 'N')->get();

    return view('MasterData::itemmaster.itemmaster.create', compact('uoms', 'categories'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'item_number' => 'required|unique:wms_m_item,item_number',
      'item_name' => 'required|max:255',
      'stock_type' => 'required|in:inventory,non-inventory',
      'uom_id' => 'required|exists:wms_m_uom,uom_id',
      'item_cat_id' => 'required|exists:wms_m_item_cat,item_cat_id',
      'standard_price' => 'required|numeric|min:0',
    ]);

    ItemMaster::create([
      'item_number' => $request->item_number,
      'item_name' => $request->item_name,
      'item_description' => $request->item_description,
      'stock_type' => $request->stock_type,
      'model' => $request->model,
      'uom_id' => $request->uom_id,
      'second_uom' => $request->second_uom,
      'volume_m3' => $request->volume_m3,
      'spq_ctn' => $request->spq_ctn,
      'spq_item' => $request->spq_item,
      'spq_pallet' => $request->spq_pallet,
      'spq_weight' => $request->spq_weight,
      'm3_pallet' => $request->m3_pallet,
      'item_cat_id' => $request->item_cat_id,
      'barcode' => $request->barcode,
      'item_rmk' => $request->remarks,
      'standard_price' => $request->standard_price,
      'coa_id' => $request->coa_id,
      'item_status' => 'active',
      'is_delete' => 'N',
      'input_by' => auth()->user()->name ?? 'system',
      'input_date' => now(),
    ]);

    return redirect()->route('itemmaster.index')->with('success', 'Item created successfully');
  }

  public function edit($id)
  {
    $item = ItemMaster::findOrFail($id);
    $uoms = Uom::where('is_delete', 'N')->get();
    $categories = ItemCategory::where('is_delete', 'N')->get();

    return view('MasterData::itemmaster.itemmaster.edit', compact('item', 'uoms', 'categories'));
  }

  public function update(Request $request, $id)
  {
    $item = ItemMaster::findOrFail($id);

    $validated = $request->validate([
      'item_number' => 'required|unique:wms_m_item,item_number,' . $id . ',item_id',
      'item_name' => 'required|max:255',
      'stock_type' => 'required|in:inventory,non-inventory',
      'uom_id' => 'required|exists:wms_m_uom,uom_id',
      'item_cat_id' => 'required|exists:wms_m_item_cat,item_cat_id',
      'standard_price' => 'required|numeric|min:0',
    ]);

    $item->update([
      'item_number' => $request->item_number,
      'item_name' => $request->item_name,
      'item_description' => $request->item_description,
      'stock_type' => $request->stock_type,
      'model' => $request->model,
      'uom_id' => $request->uom_id,
      'second_uom' => $request->second_uom,
      'volume_m3' => $request->volume_m3,
      'spq_ctn' => $request->spq_ctn,
      'spq_item' => $request->spq_item,
      'spq_pallet' => $request->spq_pallet,
      'spq_weight' => $request->spq_weight,
      'm3_pallet' => $request->m3_pallet,
      'item_cat_id' => $request->item_cat_id,
      'barcode' => $request->barcode,
      'item_rmk' => $request->remarks,
      'standard_price' => $request->standard_price,
      'coa_id' => $request->coa_id,
      'edit_by' => auth()->user()->name ?? 'system',
      'edit_date' => now(),
    ]);

    return redirect()->route('itemmaster.index')->with('success', 'Item updated successfully');
  }

  public function destroy($id)
  {
    $item = ItemMaster::findOrFail($id);
    $item->update(['is_delete' => 'Y']);

    return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
  }
}
