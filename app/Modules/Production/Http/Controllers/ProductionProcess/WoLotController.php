<?php

namespace App\Modules\Production\Http\Controllers\ProductionProcess;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\ProductionProcess\WoLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class WoLotController extends Controller
{
  private function formatQty($value)
  {
    $val = $value ?? 0;

    if (is_numeric($val) && floor($val) == $val) {
      // Format tanpa desimal untuk angka bulat
      return number_format($val, 0, '', '');
    } else {
      // Format dengan 2 desimal untuk angka desimal
      return number_format($val, 2, '.', '');
    }
  }

  public function index(Request $request)
  {
    if ($request->ajax()) {
      $data = WoLot::query()->latest();

      return DataTables::of($data)
        ->addIndexColumn()
        ->editColumn('lot_date', function ($row) {
          return $row->lot_date ? $row->lot_date->format('d-m-Y') : '';
        })
        ->editColumn('qty_per_lot', function ($row) {
          return $this->formatQty($row->qty_per_lot);
        })
        ->addColumn('action', function ($row) {
          return '
                        <div class="btn-group">
                            <a href="' . route('production.lot_number.edit', $row->lot_id) . '" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->lot_id . '" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    ';
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    return view('Production::production-process.wo-lot.index');
  }

  public function getNextLotNumber(Request $request)
  {
    $date = $request->date; // Format: YYYY-MM-DD
    if (!$date) {
      return response()->json(['lot_no' => '']);
    }

    $lotNo = $this->generateLotNumber($date);
    return response()->json(['lot_no' => $lotNo]);
  }

  private function generateLotNumber($date)
  {
    // Format date to YYYYMMDD
    $dateStr = date('Ymd', strtotime($date));
    $prefix = 'LOT' . $dateStr;

    // Find last lot number for this date
    $lastLot = WoLot::where('lot_no', 'like', $prefix . '%')
      ->orderBy('lot_no', 'desc')
      ->first();

    if ($lastLot) {
      // Extract running number (last 4 digits)
      $lastSequence = intval(substr($lastLot->lot_no, -4));
      $newSequence = $lastSequence + 1;
    } else {
      $newSequence = 1;
    }

    // Format to 4 digits with leading zeros
    return $prefix . sprintf('%04d', $newSequence);
  }

  public function create()
  {
    return view('Production::production-process.wo-lot.create');
  }

  public function store(Request $request)
  {
    $request->validate([
      'lot_date' => 'required|date',
      'qty_per_lot' => 'nullable|numeric',
      'item_desc' => 'nullable|string',
      'charge_no' => 'nullable|string',
    ]);

    // Auto generate lot number
    $lotNo = $this->generateLotNumber($request->lot_date);

    // Check duplication (just in case)
    if (WoLot::where('lot_no', $lotNo)->exists()) {
      return back()->with('error', 'Generated Lot Number already exists. Please try again or check data.');
    }

    WoLot::create([
      'lot_no' => $lotNo,
      'lot_date' => $request->lot_date,
      'qty_per_lot' => $request->qty_per_lot,
      'item_desc' => $request->item_desc,
      'charge_no' => $request->charge_no,
      'lot_create_by' => Auth::user()->name ?? 'System',
    ]);

    return redirect()->route('production.lot_number.index')->with('success', 'Lot Number created successfully: ' . $lotNo);
  }

  public function edit($id)
  {
    $lot = WoLot::findOrFail($id);

    // Format qty_per_lot untuk display di form
    $lot->qty_per_lot_display = $this->formatQty($lot->qty_per_lot);

    return view('Production::production-process.wo-lot.edit', compact('lot'));
  }

  public function update(Request $request, $id)
  {
    $request->validate([
      'lot_no' => 'required|string|max:50|unique:t_lot,lot_no,' . $id . ',lot_id',
      'lot_date' => 'required|date',
      'qty_per_lot' => 'nullable|numeric',
      'item_desc' => 'nullable|string',
      'charge_no' => 'nullable|string',
    ]);

    $lot = WoLot::findOrFail($id);

    // Format qty_per_lot - handle berbagai format input
    if ($request->has('qty_per_lot') && $request->qty_per_lot !== null) {
      $qty = $request->qty_per_lot;

      // Log original value untuk debugging
      Log::info('Original qty_per_lot value:', ['original' => $qty]);

      // Hapus semua karakter non-digit kecuali titik dan minus
      $qty = preg_replace('/[^\d\.\-]/', '', $qty);

      // Pastikan hanya ada satu titik desimal
      if (substr_count($qty, '.') > 1) {
        $parts = explode('.', $qty);
        $qty = $parts[0] . '.' . implode('', array_slice($parts, 1));
      }

      // Konversi ke float
      $qty = floatval($qty);

      Log::info('Processed qty_per_lot value:', ['processed' => $qty]);

      $request->merge(['qty_per_lot' => $qty]);
    } else {
      $request->merge(['qty_per_lot' => null]);
    }

    $lot->update($request->all());

    // Log setelah update
    Log::info('Lot updated successfully:', [
      'lot_id' => $lot->lot_id,
      'lot_no' => $lot->lot_no,
      'qty_per_lot' => $lot->qty_per_lot
    ]);

    // Response untuk AJAX
    if ($request->ajax() || $request->wantsJson()) {
      return response()->json([
        'success' => true,
        'message' => 'Lot updated successfully',
        'lot' => $lot
      ]);
    }

    // Redirect untuk regular form submission
    return redirect()->route('production.lot_number.index')
      ->with('success', 'Lot Number ' . $lot->lot_no . ' updated successfully.');
  }

  public function destroy($id)
  {
    $lot = WoLot::findOrFail($id);
    $lot->delete();

    return response()->json(['success' => true]);
  }

  public function getWorkOrders($id)
  {
    $lot = WoLot::with('workOrders.unit')->findOrFail($id);
    return response()->json([
      'lot_no' => $lot->lot_no,
      'work_orders' => $lot->workOrders
    ]);
  }

  /**
   * Update qty_per_lot based on total qty from all work orders in this lot
   */
  public function updateQtyPerLot($lotId)
  {
    $lot = WoLot::with('workOrders')->findOrFail($lotId);

    // Calculate total qty from all work orders
    $totalQty = $lot->workOrders->sum('wo_qty');

    // Update qty_per_lot
    $lot->update([
      'qty_per_lot' => $totalQty
    ]);

    return $totalQty;
  }
  /**
   * Check Lot Capacity Availability
   */
  public function checkCapacity(Request $request)
  {
    $lotId = $request->lot_id;
    $woId = $request->wo_id; // Pass this if editing a WO to exclude its current qty

    $lot = WoLot::find($lotId);
    if (!$lot) {
      return response()->json(['status' => 'error', 'message' => 'Lot not found'], 404);
    }

    $query = $lot->workOrders()->where('t_wo.is_delete', 'N');

    if ($woId) {
      $query->where('t_wo.wo_id', '!=', $woId);
    }

    $currentUsed = $query->sum('wo_qty');
    $remaining = max(0, $lot->qty_per_lot - $currentUsed);

    return response()->json([
      'status' => 'success',
      'lot_no' => $lot->lot_no,
      'lot_qty' => $lot->qty_per_lot,
      'current_used' => $currentUsed,
      'remaining' => $remaining,
      'is_full' => $remaining <= 0
    ]);
  }

  /**
   * Get Lot Details with BOM and Transactions for Report Modal
   */
  public function getLotDetails($id)
  {
    try {
      $lot = WoLot::findOrFail($id);

      // Get BOM data from first work order if exists
      $firstWo = $lot->workOrders()->with('bom')->first();

      $lotData = [
        'lot_id' => $lot->lot_id,
        'lot_no' => $lot->lot_no,
        'lot_date' => $lot->lot_date ? $lot->lot_date->format('d-m-Y') : '-',
        'qty_per_lot' => $lot->qty_per_lot ?? 0,
        'item_desc' => $lot->item_desc ?? 'Menggunakan Material',
        'charge_no' => $lot->charge_no ?? '-',
        'lot_create_by' => $lot->lot_create_by ?? '-',
        'part_no' => $firstWo && $firstWo->bom ? $firstWo->bom->part_no : '-',
        'part_name' => $firstWo && $firstWo->bom ? $firstWo->bom->part_name : '-',
      ];

      // Get all transactions from work orders in this lot
      $transactions = [];
      $workOrders = $lot->workOrders()->get();

      $cumTotalQty = 0;
      $cumNgQty = 0;
      $cumOkQty = 0;

      foreach ($workOrders as $wo) {
        // Get WO transactions from WoTransaction model
        $woTransactions = \App\Modules\Production\Models\ProductionProcess\WoTransaction::where('wo_no', $wo->wo_no)
          ->where('is_delete', 'N')
          ->orderBy('trx_date', 'asc')
          ->get();

        foreach ($woTransactions as $trx) {
          $totalQty = ($trx->ok_qty ?? 0) + ($trx->ng_qty ?? 0);
          $cumTotalQty += $totalQty;
          $cumNgQty += ($trx->ng_qty ?? 0);
          $cumOkQty += ($trx->ok_qty ?? 0);

          // Get machine name from relationship
          $machineName = '-';
          if ($trx->machine_id) {
            $machine = \App\Modules\Production\Models\PdMasterData\Machine::find($trx->machine_id);
            $machineName = $machine ? $machine->machine_name : '-';
          }

          // Get shift name
          $shiftName = $trx->shift_id ? 'Shift ' . $trx->shift_id : '-';

          $transactions[] = [
            'wo_no' => $wo->wo_no ?? '-',
            'process_name' => $trx->process_name ?? '-',
            'machine' => $machineName,
            'prod_date' => $trx->trx_date ? date('d-m-Y', strtotime($trx->trx_date)) : '-',
            'total_qty' => $totalQty,
            'cum_total' => $cumTotalQty,
            'shift' => $shiftName,
            'ng_qty' => $trx->ng_qty ?? 0,
            'cum_ng' => $cumNgQty,
            'ok_qty' => $trx->ok_qty ?? 0,
            'cum_ok' => $cumOkQty,
            'operator' => $trx->operator ?? '-',
            'ambil_qty' => 0,
            'sisa_qty' => 0,
            'remark' => $trx->notes ?? '',
          ];
        }
      }

      return response()->json([
        'success' => true,
        'data' => [
          'lot' => $lotData,
          'transactions' => $transactions
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error fetching lot details: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Failed to fetch lot details: ' . $e->getMessage()
      ], 500);
    }
  }
}
