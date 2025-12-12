<?php

namespace App\Modules\Production\Http\Controllers\ProductionProcess;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\ProductionProcess\WorkOrder;
use App\Modules\Production\Models\ProductionProcess\WorkOrderDetail;
use App\Modules\Production\Models\PdMasterData\Setting as Routing;
use App\Modules\Production\Models\PdMasterData\Bom;
use App\Modules\Production\Models\ProductionProcess\WoLot as Lot;
use App\Modules\MasterData\Models\ItemMaster as Item;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
  public function index(Request $request)
  {
    if ($request->ajax()) {
      $data = WorkOrder::select('t_wo.*', DB::raw('MAX(t_wo_completion_detail.process_name) as active_process'))
        ->leftJoin('t_wo_completion', 't_wo.wo_id', '=', 't_wo_completion.wo_id')
        ->leftJoin('t_wo_completion_detail', function ($join) {
          $join->on('t_wo_completion.completion_id', '=', 't_wo_completion_detail.completion_id')
            ->whereIn('t_wo_completion_detail.status', ['Running', 'Paused']);
        })
        ->with('routing')
        ->where('t_wo.is_delete', 'N')
        ->whereIn('t_wo.wo_status', ['Release', 'Draft', 'On Process', 'Built']) // Ambil yang statusnya Progress / Belum Selesai
        ->groupBy('t_wo.wo_id')
        ->orderBy('t_wo.wo_id', 'desc');

      return DataTables::of($data)
        ->addIndexColumn()
        ->addColumn('process_name', function ($row) {
          if ($row->active_process) {
            return $row->active_process . ' (On Process)';
          }
          return $row->routing ? $row->routing->routing_name : '-';
        })
        ->addColumn('item_number', function ($row) {
          return $row->part_no;
        })
        ->addColumn('item_name', function ($row) {
          return $row->part_name;
        })
        ->editColumn('wo_date', function ($row) {
          return $row->wo_date ? $row->wo_date->format('d-m-Y') : '-';
        })
        ->editColumn('prod_date', function ($row) {
          return $row->prod_date ? $row->prod_date->format('d-m-Y') : '-';
        })
        ->editColumn('wo_status', function ($row) {
          if ($row->wo_status == 'Release') {
            return '<span class="badge bg-success">Release</span>';
          } elseif ($row->wo_status == 'Draft') {
            return '<span class="badge bg-secondary">Draft</span>';
          } elseif ($row->wo_status == 'On Process') {
            return '<span class="badge bg-warning">On Process</span>';
          } elseif ($row->wo_status == 'Built') {
            return '<span class="badge bg-primary">Built</span>';
          } else {
            return '<span class="badge bg-info">' . $row->wo_status . '</span>';
          }
        })
        ->addColumn('action', function ($row) {
          $editUrl = route('production.work-order.edit', $row->wo_id);
          $deleteUrl = route('production.work-order.destroy', $row->wo_id);

          return '
                        <a href="' . $editUrl . '" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->wo_id . '" data-url="' . $deleteUrl . '">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ';
        })
        ->editColumn('wo_no', function ($row) {
          return '<a href="' . route('production.work-order.show', $row->wo_id) . '">' . $row->wo_no . '</a>';
        })
        ->rawColumns(['wo_status', 'action', 'wo_no'])
        ->make(true);
    }

    return view('Production::production-process.work-order.index');
  }

  public function create()
  {
    // Generate Auto WO Number
    $today = date('Ymd');
    $lastWo = WorkOrder::where('wo_no', 'like', 'WO' . $today . '%')
      ->orderBy('wo_no', 'desc')
      ->first();

    if ($lastWo) {
      $lastSequence = intval(substr($lastWo->wo_no, -4));
      $newSequence = $lastSequence + 1;
    } else {
      $newSequence = 1;
    }

    $autoWoNo = 'WO' . $today . sprintf('%04d', $newSequence);
    // Filter items yang punya BOM aktif
    $items = Item::with(['uom', 'category'])
      ->where('is_delete', 'N')
      ->has('bom')
      ->has('setting')
      ->get();

    // Get all lots
    $lots = Lot::orderBy('lot_no', 'desc')->get();

    return view('Production::production-process.work-order.create', compact('autoWoNo', 'items', 'lots'));
  }

  public function store(Request $request)
  {
    $request->validate([
      'wo_no' => 'required|unique:t_wo,wo_no',
      'wo_date' => 'required|date',
      'prod_date' => 'required|date',
      'wo_qty' => 'required|numeric|min:0',
      'part_no' => 'required|string',
      'part_name' => 'required|string',
      'uom' => 'nullable|string',
      'wo_status' => 'required|string',
      'lot_id' => 'nullable|exists:t_lot,lot_id',
    ]);

    // Check Lot Capacity Logic BEFORE Creation
    if ($request->lot_id) {
      $lot = Lot::findOrFail($request->lot_id);
      $currentTotalWoQty = WorkOrder::where('lot_id', $request->lot_id)
        ->where('is_delete', 'N')
        ->sum('wo_qty');

      $newTotal = $currentTotalWoQty + $request->wo_qty;

      if ($newTotal > $lot->qty_per_lot) {
        return back()->with('error', "Failed to create Work Order: Total Work Order Qty ({$newTotal}) exceeds Lot Qty ({$lot->qty_per_lot}). Remaining: " . ($lot->qty_per_lot - $currentTotalWoQty))->withInput();
      }
    }

    DB::beginTransaction();
    try {
      $wo = WorkOrder::create([
        'wo_no' => $request->wo_no,
        'wo_date' => $request->wo_date,
        'prod_date' => $request->prod_date,
        'part_no' => $request->part_no,
        'part_name' => $request->part_name,
        'uom_id' => $request->uom,
        'wo_qty' => $request->wo_qty,
        'wo_rmk' => $request->wo_rmk,
        'wo_status' => $request->wo_status,
        'lot_id' => $request->lot_id,
        'input_by' => auth()->user()->name ?? 'system',
        'is_delete' => 'N'
      ]);

      // Calculate and Save BOM Details (Materials)
      $bom = Bom::with('details')
        ->where('part_no', $request->part_no)
        ->where('is_delete', 'N')
        ->where('bom_status', 1)
        ->first();

      if ($bom) {
        foreach ($bom->details as $bomDetail) {
          $bomQtyPerUnit = str_replace(',', '.', $bomDetail->bom_dtl_qty);
          $totalRequiredQty = floatval($bomQtyPerUnit) * floatval($request->wo_qty);

          WorkOrderDetail::create([
            'wo_no' => $wo->wo_no,
            'item_id' => $bomDetail->part_no,
            'item_name' => $bomDetail->part_name,
            'item_desc' => $bomDetail->part_desc,
            'wo_qty' => $totalRequiredQty,
            'bom_qty' => $bomQtyPerUnit
          ]);
        }
      }

      DB::commit();
      return redirect()->route('production.work-order.index')->with('success', 'Work Order created successfully');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Failed to create Work Order: ' . $e->getMessage())->withInput();
    }
  }

  public function show($id)
  {
    $wo = WorkOrder::with('routing.details')->findOrFail($id);
    return view('Production::production-process.work-order.show', compact('wo'));
  }

  public function edit($id)
  {
    $wo = WorkOrder::findOrFail($id);
    // Filter items yang punya BOM aktif
    $items = Item::with(['uom', 'category'])
      ->where('is_delete', 'N')
      ->has('bom')
      ->has('setting')
      ->get();

    // Get all lots
    $lots = Lot::orderBy('lot_no', 'desc')->get();

    return view('Production::production-process.work-order.edit', compact('wo', 'items', 'lots'));
  }

  public function update(Request $request, $id)
  {
    $request->validate([
      'wo_date' => 'required|date',
      'prod_date' => 'required|date',
      'wo_qty' => 'required|numeric|min:0',
      'part_no' => 'required|string',
      'part_name' => 'required|string',
      'uom' => 'nullable|string',
      'wo_status' => 'required|string',
      'lot_id' => 'nullable|exists:t_lot,lot_id',
    ]);

    // Check Lot Capacity Logic BEFORE Update
    if ($request->lot_id) {
      $lot = Lot::findOrFail($request->lot_id);
      $currentTotalWoQty = WorkOrder::where('lot_id', $request->lot_id)
        ->where('is_delete', 'N')
        ->where('wo_id', '!=', $id) // Exclude current WO
        ->sum('wo_qty');

      $newTotal = $currentTotalWoQty + $request->wo_qty;

      if ($newTotal > $lot->qty_per_lot) {
        return back()->with('error', "Failed to update Work Order: Total Work Order Qty ({$newTotal}) exceeds Lot Qty ({$lot->qty_per_lot}). Remaining: " . ($lot->qty_per_lot - $currentTotalWoQty))->withInput();
      }
    }

    DB::beginTransaction();
    try {
      $wo = WorkOrder::findOrFail($id);

      $wo->update([
        'wo_date' => $request->wo_date,
        'prod_date' => $request->prod_date,
        'part_no' => $request->part_no,
        'part_name' => $request->part_name,
        'uom_id' => $request->uom,
        'wo_qty' => $request->wo_qty,
        'wo_rmk' => $request->wo_rmk,
        'wo_status' => $request->wo_status,
        'lot_id' => $request->lot_id,
        'edit_by' => auth()->user()->name ?? 'system',
        'edit_time' => now(),
      ]);

      DB::commit();
      return redirect()->route('production.work-order.index')->with('success', 'Work Order updated successfully');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Failed to update Work Order: ' . $e->getMessage())->withInput();
    }
  }

  public function destroy($id)
  {
    $wo = WorkOrder::findOrFail($id);
    $wo->update([
      'is_delete' => 'Y',
      'edit_by' => auth()->user()->name ?? 'system',
      'edit_time' => now()
    ]);



    return response()->json(['success' => true, 'message' => 'Work Order deleted successfully']);
  }

  public function report($id)
  {
    $wo = WorkOrder::with('routing.details')->findOrFail($id);
    return view('Production::production-process.work-order.report', compact('wo'));
  }

  // AJAX to get Part Details (Routing and BOM info if needed)
  public function getPartDetails(Request $request)
  {
    $partNo = $request->part_no;

    // Get Routing (Process List)
    $routing = Routing::with('details')
      ->where('part_no', $partNo)
      ->where('is_delete', 'N')
      ->where('routing_status', 1)
      ->first();

    $processes = [];
    if ($routing) {
      foreach ($routing->details as $detail) {
        $processes[] = [
          'process_name' => $detail->process_name,
          'process_desc' => $detail->process_desc,
          'cycle_time' => $detail->cycle_time_second
        ];
      }
    }

    return response()->json([
      'processes' => $processes
    ]);
  }
}
