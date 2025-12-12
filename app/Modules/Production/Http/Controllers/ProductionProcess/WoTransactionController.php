<?php

namespace App\Modules\Production\Http\Controllers\ProductionProcess;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\ProductionProcess\WoTransaction;
use App\Modules\Production\Models\ProductionProcess\WorkOrder;
use App\Modules\Production\Models\PdMasterData\MasterProcess;
use App\Modules\MasterData\Models;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WoTransactionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = WoTransaction::with(['workOrder', 'process'])
                ->where('is_delete', 'N')
                ->orderBy('trx_id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('wo_no', function ($row) {
                    return $row->wo_no ?? '-';
                })
                ->editColumn('item', function ($row) {
                    return $row->workOrder ? $row->workOrder->part_name : '-';
                })
                ->editColumn('process_name', function ($row) {
                    return $row->process_name ?? '-';
                })
                ->editColumn('good_qty', function ($row) {
                    return (float) $row->good_qty;
                })
                ->editColumn('ng_qty', function ($row) {
                    return (float) $row->ng_qty;
                })
                ->editColumn('prod_time', function ($row) {
                    return $row->prod_time ?? '-';
                })
                ->editColumn('downtime', function ($row) {
                    return $row->downtime ?? '-';
                })
                ->editColumn('oee', function ($row) {
                    return $row->oee ? $row->oee . '%' : '-';
                })
                ->editColumn('wo_date', function ($row) {
                    return $row->workOrder && $row->workOrder->wo_date
                        ? $row->workOrder->wo_date->format('d-m-Y')
                        : '-';
                })
                ->editColumn('prod_date', function ($row) {
                    return $row->workOrder && $row->workOrder->prod_date
                        ? $row->workOrder->prod_date->format('d-m-Y')
                        : '-';
                })
                ->addColumn('action', function ($row) {
                    $deleteUrl = route('production.wo-transaction.destroy', $row->trx_id);
                    $showUrl = route('production.wo-transaction.show', $row->trx_id);

                    return '
                        <a href="' . $showUrl . '" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> 
                        </a>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->trx_id . '" data-url="' . $deleteUrl . '">
                            <i class="fas fa-trash"></i> 
                        </button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('Production::production-process.wo-transaction.index');
    }

    public function create()
    {
        // Generate Auto Transaction Number
        $today = date('Ymd');
        $lastTrx = WoTransaction::where('trx_no', 'like', 'WX' . $today . '%')
            ->orderBy('trx_no', 'desc')
            ->first();

        if ($lastTrx) {
            $lastSequence = intval(substr($lastTrx->trx_no, -4));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        $autoTrxNo = 'WX' . $today . sprintf('%04d', $newSequence);

        // Get Work Orders
        $workOrders = WorkOrder::where('is_delete', 'N')
            ->where('wo_status', 'Release')
            ->get();

        // Fetch Master Data
        // Machines from m_machine
        $machines = \App\Modules\Production\Models\PdMasterData\Machine::all();

        // Users for Supervisor & Operator
        $users = \App\Modules\MasterData\Models\MUser::all();

        // Setup options
        $shifts = ['1', '2', '3'];

        return view('Production::production-process.wo-transaction.create', compact('autoTrxNo', 'workOrders', 'machines', 'users', 'shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'trx_no' => 'required|unique:t_wo_transaction,trx_no',
            'wo_no' => 'required', // This is actually wo_id from the form
            'process_id' => 'required',
            'shift' => 'required',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
            'good_qty' => 'required|numeric|min:0',
            'ng_qty' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Get WO details - wo_no field actually contains wo_id from the form
            $wo = WorkOrder::find($request->wo_no);

            if (!$wo) {
                return back()->with('error', 'Work Order not found')->withInput();
            }

            // Get Process details
            $process = MasterProcess::find($request->process_id);

            // Calculate production time and downtime
            $startDateTime = \Carbon\Carbon::parse($request->start_date . ' ' . $request->start_time);
            $endDateTime = \Carbon\Carbon::parse($request->end_date . ' ' . $request->end_time);

            $totalMinutes = $startDateTime->diffInMinutes($endDateTime);
            $downtimeMinutes = $request->downtime ? $this->parseTimeToMinutes($request->downtime) : 0;
            $prodTimeMinutes = $totalMinutes - $downtimeMinutes;

            // Format times as HH:MM
            $prodTime = $this->formatMinutesToTime($prodTimeMinutes);
            $downtime = $request->downtime ?? '00:00';

            // Calculate OEE (simplified calculation)
            $totalQty = floatval($request->good_qty) + floatval($request->ng_qty);
            $oee = $totalQty > 0 ? round((floatval($request->good_qty) / $totalQty) * 100, 2) : 0;

            WoTransaction::create([
                'trx_no' => $request->trx_no,
                'wo_no' => $wo->wo_no, // Get actual wo_no from WorkOrder record
                'wo_id' => $wo->wo_id,
                'process_id' => $request->process_id,
                'process_name' => $process ? $process->process_name : '',
                'supervisor' => $request->supervisor,
                'operator' => $request->operator,
                'machine' => $request->machine,
                'shift' => $request->shift,
                'start_date' => $request->start_date,
                'start_time' => $request->start_time,
                'end_date' => $request->end_date,
                'end_time' => $request->end_time,
                'remain_qty' => $request->remain_qty ?? 0,
                'good_qty' => $request->good_qty,
                'ng_qty' => $request->ng_qty,
                'downtime' => $downtime,
                'prod_time' => $prodTime,
                'oee' => $oee,
                'input_by' => Auth::check() ? Auth::user()->name : 'system',
                'input_time' => now(),
                'is_delete' => 'N'
            ]);

            DB::commit();
            return redirect()->route('production.wo-transaction.index')->with('success', 'Work Order Transaction created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create Work Order Transaction: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $transaction = WoTransaction::with(['workOrder', 'process'])->findOrFail($id);
        return view('Production::production-process.wo-transaction.show', compact('transaction'));
    }

    public function destroy($id)
    {
        $transaction = WoTransaction::findOrFail($id);
        $transaction->update([
            'is_delete' => 'Y',
            'edit_by' => Auth::check() ? Auth::user()->name : 'system',
            'edit_time' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Work Order Transaction deleted successfully']);
    }

    // Helper function to parse time string (HH:MM) to minutes
    private function parseTimeToMinutes($timeString)
    {
        $parts = explode(':', $timeString);
        if (count($parts) == 2) {
            return (intval($parts[0]) * 60) + intval($parts[1]);
        }
        return 0;
    }

    // Helper function to format minutes to HH:MM
    private function formatMinutesToTime($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    // AJAX to get WO details
    public function getWoDetails(Request $request)
    {
        $woNo = $request->wo_no;
        // Get WO details (assuming WO has Part No which links to Routing)
        $wo = WorkOrder::where('wo_no', $woNo)->where('is_delete', 'N')->first();

        if (!$wo) {
            return response()->json(['error' => 'Work Order not found'], 404);
        }

        // Find active Routing for the Part No
        // Relaxed check: First try to find Active one, if not, find any non-deleted one.
        $partNo = trim($wo->part_no);

        $routing = \App\Modules\Production\Models\PdMasterData\Setting::with(['details.masterProcess'])
            ->where('part_no', $partNo)
            ->where('is_delete', 'N')
            ->orderBy('routing_status', 'desc') // Prefer Active (1) over Inactive (0)
            ->first();

        $processes = [];
        if ($routing && $routing->details) {
            foreach ($routing->details as $detail) {
                $processName = $detail->process_name;

                // Fallback to Master Process name if detail name is missing
                if (empty($processName) && $detail->masterProcess) {
                    $processName = $detail->masterProcess->process_name;
                }

                $processes[] = [
                    'process_id' => $detail->process_id,
                    'process_name' => $processName ?? 'Unknown Process',
                    'cycle_time' => $detail->cycle_time_second
                ];
            }
        }

        // Calculate Remain Qty = WO Qty - Total Processed (Good + NG)
        $transactions = WoTransaction::where('wo_no', $woNo)
            ->where('is_delete', 'N')
            ->get();

        $totalProcessed = $transactions->sum('good_qty') + $transactions->sum('ng_qty');

        $remainQty = $wo->wo_qty - $totalProcessed;

        return response()->json([
            'wo_qty' => floatval($wo->wo_qty),
            'remain_qty' => floatval($remainQty),
            'part_name' => $wo->part_name,
            'processes' => $processes
        ]);
    }
}
