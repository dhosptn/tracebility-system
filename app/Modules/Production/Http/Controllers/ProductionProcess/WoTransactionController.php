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
use Illuminate\Support\Facades\Log;

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
                ->editColumn('ok_qty', function ($row) {
                    return number_format($row->ok_qty ?? 0);
                })
                ->editColumn('ng_qty', function ($row) {
                    return number_format($row->ng_qty ?? 0);
                })
                ->editColumn('prod_time', function ($row) {
                    if ($row->start_time && $row->end_time) {
                        $start = \Carbon\Carbon::parse($row->start_time);
                        $end = \Carbon\Carbon::parse($row->end_time);
                        $diffInMinutes = $start->diffInMinutes($end);
                        $hours = floor($diffInMinutes / 60);
                        $minutes = $diffInMinutes % 60;
                        return sprintf('%02d:%02d', $hours, $minutes);
                    }
                    return '-';
                })
                ->editColumn('downtime', function ($row) {
                    // Downtime tidak ada di database baru, return default
                    return '00:00';
                })
                ->editColumn('oee', function ($row) {
                    // Calculate OEE: (OK Qty / Actual Qty) * 100
                    if ($row->actual_qty > 0) {
                        $oee = round(($row->ok_qty / $row->actual_qty) * 100, 2);
                        return $oee . '%';
                    }
                    return '-';
                })
                ->editColumn('wo_date', function ($row) {
                    return $row->workOrder && $row->workOrder->wo_date
                        ? $row->workOrder->wo_date->format('d-m-Y')
                        : '-';
                })
                ->editColumn('prod_date', function ($row) {
                    return $row->trx_date
                        ? \Carbon\Carbon::parse($row->trx_date)->format('d-m-Y')
                        : '-';
                })
                ->addColumn('action', function ($row) {
                    $deleteUrl = route('production.wo-transaction.destroy', $row->trx_id);
                    $showUrl = route('production.wo-transaction.show', $row->trx_id);

                    return '
                        <a href="' . $showUrl . '" class="btn btn-sm btn-info" title="View Details">
                            <i class="fas fa-eye"></i> 
                        </a>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->trx_id . '" data-url="' . $deleteUrl . '" title="Delete">
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

        // Users for Supervisor & Operator - filtered by role
        $supervisors = \App\Modules\MasterData\Models\MUser::where('role', 'supervisor')->get();
        $operators = \App\Modules\MasterData\Models\MUser::where('role', 'operator')->get();

        // Setup options
        $shifts = ['1', '2', '3'];

        return view('Production::production-process.wo-transaction.create', compact('autoTrxNo', 'workOrders', 'machines', 'supervisors', 'operators', 'shifts'));
    }

    public function store(Request $request)
    {
        // Handle Auto Save from TV Display (bypassing validation for manual input)
        if ($request->has('monitoring_id')) {
            return $this->autoSave($request);
        }

        $request->validate([
            'trx_no' => 'required|unique:t_wo_transaction,trx_no',
            'wo_no' => 'required',
            'process_id' => 'required',
            'shift' => 'required',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'required|date',
            'end_time' => 'required',
            'good_qty' => 'required|numeric|min:0',
            'ng_qty' => 'required|numeric|min:0',
        ], [
            'trx_no.required' => 'Transaction number is required',
            'trx_no.unique' => 'Transaction number already exists',
            'wo_no.required' => 'Work Order is required',
            'process_id.required' => 'Process is required',
            'shift.required' => 'Shift is required',
            'start_date.required' => 'Start date is required',
            'start_time.required' => 'Start time is required',
            'end_date.required' => 'End date is required',
            'end_time.required' => 'End time is required',
            'good_qty.required' => 'Good quantity is required',
            'ng_qty.required' => 'NG quantity is required',
        ]);

        DB::beginTransaction();
        try {
            Log::info('WoTransaction Store Request:', $request->all());

            // Get WO details - wo_no field actually contains wo_id from the form
            $wo = WorkOrder::find($request->wo_no);

            if (!$wo) {
                Log::error('WorkOrder not found with ID: ' . $request->wo_no);
                return back()->with('error', 'Work Order not found')->withInput();
            }

            // Get Process details from SettingDetail (routing detail)
            $settingDetail = \App\Modules\Production\Models\PdMasterData\SettingDetail::find($request->process_id);
            $cycleTime = 0;
            $processName = '';

            if ($settingDetail) {
                $cycleTime = $settingDetail->cycle_time_second ?? 0;
                $processName = $settingDetail->process_name ?? '';
            } else {
                // Fallback to MasterProcess if SettingDetail not found
                $process = MasterProcess::find($request->process_id);
                if ($process) {
                    $cycleTime = $process->cycle_time_second ?? 0;
                    $processName = $process->process_name ?? '';
                }
            }

            // Create start and end datetime
            $startDateTime = \Carbon\Carbon::parse($request->start_date . ' ' . $request->start_time);
            $endDateTime = \Carbon\Carbon::parse($request->end_date . ' ' . $request->end_time);

            // Calculate actual qty
            $actualQty = intval($request->good_qty ?? 0) + intval($request->ng_qty ?? 0);

            $transactionData = [
                'trx_no' => $request->trx_no,
                'trx_date' => $request->start_date,
                'wo_id' => $wo->wo_id,
                'wo_no' => $wo->wo_no,
                'process_id' => $request->process_id,
                'process_name' => $processName,
                'cycle_time' => $cycleTime,
                'supervisor' => $request->supervisor,
                'operator' => $request->operator,
                'machine_id' => $request->machine ? intval($request->machine) : null,
                'shift_id' => $request->shift ? intval($request->shift) : null,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'target_qty' => intval($request->remain_qty ?? 0),
                'actual_qty' => $actualQty,
                'ok_qty' => intval($request->good_qty ?? 0),
                'ng_qty' => intval($request->ng_qty ?? 0),
                'status' => 'Draft',
                'notes' => $request->notes,
                'input_by' => Auth::check() ? Auth::user()->name : 'system',
                'input_time' => now(),
                'is_delete' => 'N'
            ];

            Log::info('WoTransaction Data to be created:', $transactionData);

            $transaction = WoTransaction::create($transactionData);

            Log::info('WoTransaction created successfully with ID: ' . $transaction->trx_id);

            DB::commit();
            return redirect()->route('production.wo-transaction.index')->with('success', 'Work Order Transaction created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WoTransaction Store Error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
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
        try {
            $transaction = WoTransaction::findOrFail($id);
            $transaction->update([
                'is_delete' => 'Y',
                'edit_by' => Auth::check() ? Auth::user()->name : 'system',
                'edit_time' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Work Order Transaction deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('WoTransaction Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Work Order Transaction: ' . $e->getMessage()
            ], 500);
        }
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

    public function autoSave(Request $request)
    {
        try {
            Log::info('Auto Save Triggered for Monitoring ID: ' . $request->monitoring_id);
            
            $monitoring = \App\Modules\Production\Models\ProductionProcess\ProductionMonitoring::findOrFail($request->monitoring_id);

            // Check if transaction already exists for this WO and Process to prevent duplicates
            // We check for transactions created today
            $exists = WoTransaction::where('wo_no', $monitoring->wo_no)
                ->where('process_id', $monitoring->process_id)
                ->whereDate('trx_date', now()->toDateString())
                ->exists();

            if ($exists) {
                 return response()->json(['success' => true, 'message' => 'Transaction already exists']);
            }

            DB::beginTransaction();

            // Generate Auto Transaction Number
            $today = date('Ymd');
            $lastTrx = WoTransaction::where('trx_no', 'like', 'WX' . $today . '%')
                ->orderBy('trx_no', 'desc')
                ->first();

            $newSequence = $lastTrx ? intval(substr($lastTrx->trx_no, -4)) + 1 : 1;
            $trxNo = 'WX' . $today . sprintf('%04d', $newSequence);

            // Get Work Order details
            $wo = WorkOrder::where('wo_no', $monitoring->wo_no)->first();

            $transaction = WoTransaction::create([
                'trx_no' => $trxNo,
                'trx_date' => now()->toDateString(),
                'wo_id' => $wo ? $wo->wo_id : null,
                'wo_no' => $monitoring->wo_no,
                'process_id' => $monitoring->process_id,
                'process_name' => $monitoring->process_name,
                'cycle_time' => $monitoring->cycle_time,
                'supervisor' => $monitoring->supervisor,
                'operator' => $monitoring->operator,
                'machine_id' => $monitoring->machine_id,
                'shift_id' => $monitoring->shift_id,
                'start_time' => $monitoring->start_time,
                'end_time' => now(),
                'target_qty' => $monitoring->wo_qty,
                'actual_qty' => $monitoring->qty_actual,
                'ok_qty' => $monitoring->qty_ok,
                'ng_qty' => $monitoring->qty_ng,
                'status' => 'Draft',
                'notes' => 'Auto-generated from TV Display',
                'input_by' => 'System',
                'input_time' => now(),
                'is_delete' => 'N'
            ]);

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Transaction auto-saved successfully', 
                'trx_no' => $trxNo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Save Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
