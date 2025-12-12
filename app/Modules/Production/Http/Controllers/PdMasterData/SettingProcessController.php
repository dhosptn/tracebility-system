<?php

namespace App\Modules\Production\Http\Controllers\PdMasterData;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\PdMasterData\Setting;
use App\Modules\Production\Models\PdMasterData\SettingDetail;
use App\Modules\Production\Models\PdMasterData\MasterProcess;
use App\Modules\MasterData\Models\ItemMaster as Item;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SettingProcessController extends Controller
{
  public function index(Request $request)
  {
    if ($request->ajax()) {
      $data = Setting::where('is_delete', 'N')
        ->orderBy('routing_id', 'desc')
        ->get();

      return DataTables::of($data)
        ->addIndexColumn()
        ->addColumn('routing_name_link', function ($row) {
          return '<a href="' . route('production.setting-process.show', $row->routing_id) . '" class="text-primary">' . $row->routing_name . '</a>';
        })
        ->editColumn('routing_active_date', function ($row) {
          return $row->routing_active_date ? $row->routing_active_date->format('d-m-Y') : '-';
        })
        ->editColumn('routing_status', function ($row) {
          if ($row->routing_status == 1) {
            return '<span class="badge bg-success">Active</span>';
          } else {
            return '<span class="badge bg-secondary">Inactive</span>';
          }
        })
        ->addColumn('action', function ($row) {
          $editUrl = route('production.setting-process.edit', $row->routing_id);
          $deleteUrl = route('production.setting-process.destroy', $row->routing_id);

          return '
            <a href="' . $editUrl . '" class="btn btn-sm btn-warning">
              <i class="fas fa-edit"></i> Edit
            </a>
            <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->routing_id . '" data-url="' . $deleteUrl . '">
              <i class="fas fa-trash"></i> Delete
            </button>
          ';
        })
        ->rawColumns(['routing_name_link', 'routing_status', 'action'])
        ->make(true);
    }

    return view('Production::pd-masterdata.setting-process.index');
  }

  public function create()
  {
    $processes = MasterProcess::where('is_delete', 'N')->get();
    $items = Item::with(['uom', 'category'])
      ->where('is_delete', 'N')
      ->get();
    return view('Production::pd-masterdata.setting-process.create', compact('processes', 'items'));
  }

  public function store(Request $request)
  {
    $request->validate([
      'routing_name' => 'required|string|max:100',
      'part_no' => 'nullable|string|max:100',
      'part_name' => 'nullable|string|max:100',
      'part_desc' => 'nullable|string|max:300',
      'routing_rmk' => 'nullable|string|max:100',
      'routing_active_date' => 'required|date',
      'routing_status' => 'required|in:0,1',
      'process_id' => 'required|array|min:1',
      'process_id.*' => 'required|exists:m_process,proces_id',
      'cycle_time_second' => 'required|array',
      'cycle_time_second.*' => 'required|numeric|min:0',
      'process_desc' => 'nullable|array',
    ]);

    // Create routing header
    $routing = Setting::create([
      'routing_name' => $request->routing_name,
      'part_no' => $request->part_no,
      'part_name' => $request->part_name,
      'part_desc' => $request->part_desc,
      'routing_rmk' => $request->routing_rmk,
      'routing_active_date' => $request->routing_active_date,
      'routing_status' => $request->routing_status,
      'input_by' => auth()->user()->name ?? 'system',
      'input_date' => now(),
      'is_delete' => 'N'
    ]);

    // Create routing details
    if ($request->process_id) {
      foreach ($request->process_id as $key => $processId) {
        $process = MasterProcess::find($processId);

        SettingDetail::create([
          'routing_id' => $routing->routing_id,
          'process_id' => $processId,
          'process_name' => $process ? $process->process_name : null,
          'process_desc' => $request->process_desc[$key] ?? null,
          'cycle_time_second' => $request->cycle_time_second[$key],
          'urutan_proses' => $key + 1
        ]);
      }
    }

    return redirect()->route('production.setting-process.index')
      ->with('success', 'Routing created successfully');
  }

  public function show($id)
  {
    $routing = Setting::with('details')->findOrFail($id);
    return view('Production::pd-masterdata.setting-process.show', compact('routing'));
  }

  public function edit($id)
  {
    $routing = Setting::with('details')->findOrFail($id);
    $processes = MasterProcess::where('is_delete', 'N')->get();
    $items = Item::with(['uom', 'category'])
      ->where('is_delete', 'N')
      ->get();
    return view('Production::pd-masterdata.setting-process.edit', compact('routing', 'processes', 'items'));
  }

  public function update(Request $request, $id)
  {
    $request->validate([
      'routing_name' => 'required|string|max:100',
      'part_no' => 'nullable|string|max:100',
      'part_name' => 'nullable|string|max:100',
      'part_desc' => 'nullable|string|max:300',
      'routing_rmk' => 'nullable|string|max:100',
      'routing_active_date' => 'required|date',
      'routing_status' => 'required|in:0,1',
      'process_id' => 'required|array|min:1',
      'process_id.*' => 'required|exists:m_process,proces_id',
      'cycle_time_second' => 'required|array',
      'cycle_time_second.*' => 'required|numeric|min:0',
      'process_desc' => 'nullable|array',
    ]);

    $routing = Setting::findOrFail($id);

    // Update routing header
    $routing->update([
      'routing_name' => $request->routing_name,
      'part_no' => $request->part_no,
      'part_name' => $request->part_name,
      'part_desc' => $request->part_desc,
      'routing_rmk' => $request->routing_rmk,
      'routing_active_date' => $request->routing_active_date,
      'routing_status' => $request->routing_status,
      'edit_by' => auth()->user()->name ?? 'system',
      'edit_date' => now(),
    ]);

    // Delete old details
    SettingDetail::where('routing_id', $id)->delete();

    // Create new details
    if ($request->process_id) {
      foreach ($request->process_id as $key => $processId) {
        $process = MasterProcess::find($processId);

        SettingDetail::create([
          'routing_id' => $routing->routing_id,
          'process_id' => $processId,
          'process_name' => $process ? $process->process_name : null,
          'process_desc' => $request->process_desc[$key] ?? null,
          'cycle_time_second' => $request->cycle_time_second[$key],
          'urutan_proses' => $key + 1
        ]);
      }
    }

    return redirect()->route('production.setting-process.index')
      ->with('success', 'Routing updated successfully');
  }

  public function destroy($id)
  {
    $routing = Setting::findOrFail($id);
    $routing->update([
      'is_delete' => 'Y',
      'edit_by' => auth()->user()->name ?? 'system',
      'edit_date' => now(),
    ]);

    return response()->json(['success' => true, 'message' => 'Routing deleted successfully']);
  }
}
