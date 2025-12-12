<?php

namespace App\Modules\Production\Http\Controllers\PdMasterData;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\PdMasterData\MasterProcess;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class MasterProcessController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    if ($request->ajax()) {
      $data = MasterProcess::where('is_delete', 'N')
        ->select('proces_id', 'process_name', 'process_desc', 'input_by', 'input_time');

      return DataTables::of($data)
        ->addIndexColumn()
        ->addColumn('action', function ($row) {
          return '
                        <div class="btn-group">
                            <a href="' . route('master-process.edit', $row->proces_id) . '" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->proces_id . '" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    ';
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    return view('Production::pd-masterdata.master-process.index');
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    return view('Production::pd-masterdata.master-process.create');
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $request->validate([
      'process_name' => 'required|string|max:255',
      'process_desc' => 'nullable|string',
    ]);

    try {
      MasterProcess::create([
        'process_name' => $request->process_name,
        'process_desc' => $request->process_desc,
        'input_by'     => auth()->user()->name ?? 'System',
        'input_time'   => now(),
        'is_delete'    => 'N'
      ]);

      return redirect()->route('master-process.index')
        ->with('success', 'Process berhasil ditambahkan.');
    } catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
    $process = MasterProcess::findOrFail($id);
    return view('Production::pd-masterdata.master-process.edit', compact('process'));
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $request->validate([
      'process_name' => 'required|string|max:255',
      'process_desc' => 'nullable|string',
    ]);

    try {
      $process = MasterProcess::findOrFail($id);
      $process->update([
        'process_name' => $request->process_name,
        'process_desc' => $request->process_desc,
        'edit_by'      => auth()->user()->name ?? 'System',
        'edit_date'    => now(),
      ]);

      return redirect()->route('master-process.index')
        ->with('success', 'Process berhasil diperbarui.');
    } catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }
  }

  /**
   * Remove the specified resource from storage (soft delete).
   */
  public function destroy(string $id)
  {
    try {
      $process = MasterProcess::findOrFail($id);
      $process->update([
        'is_delete' => 'Y',
        'edit_by'   => auth()->user()->name ?? 'System',
        'edit_date' => now(),
      ]);

      return response()->json(['success' => true, 'message' => 'Process berhasil dihapus.']);
    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }
}
