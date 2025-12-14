<?php

namespace App\Modules\Production\Http\Controllers\PdMasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Modules\Production\Models\PdMasterData\Bom;
use App\Modules\Production\Models\PdMasterData\BomDetail;
use Illuminate\Support\Facades\DB;
// Ganti dengan model BOM Anda yang sebenarnya
// use App\Models\Bom; 

class BomController extends Controller
{
  /**
   * Menampilkan daftar Bill of Materials (BOM).
   *
   * @return \Illuminate\View\View
   */
  public function index(Request $request)
  {
    if ($request->ajax()) {

      $data = Bom::where('is_delete', 'N') // tampilkan hanya yang aktif
        ->select(
          'bom_id',
          'bom_name',
          'part_no',
          'part_name',
          'bom_active_date',
          'bom_status'
        );

      return DataTables::of($data)
        ->addIndexColumn()
        ->editColumn('bom_status', function ($row) {
          return $row->bom_status == 1 ? '<span class="badge bg-success">Active</span>' :
            '<span class="badge bg-secondary">Inactive</span>';
        })
        ->addColumn('action', function ($row) {
          return '
                        <div class="btn-group">
                            <a href="' . route('production.bom.edit', $row->bom_id) . '" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' . $row->bom_id . '" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    ';
        })
        ->editColumn('bom_name', function ($row) {
          return '<a href="' . route('production.bom.show', $row->bom_id) . '">' . $row->bom_name . '</a>';
        })
        ->rawColumns(['bom_name', 'action', 'bom_status'])
        ->make(true);
    }

    return view('Production::pd-masterdata.bom.index');
  }

  /**
   * Menampilkan form untuk membuat BOM baru.
   *
   * @return \Illuminate\View\View
   */
  public function create()
  {
    return view('Production::pd-masterdata.bom.create');
  }

  /**
   * Menyimpan data BOM baru ke database.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(Request $request)
  {
    // Basic validation
    $request->validate([
      'bom_name' => 'required|string|max:100',
      'part_no' => 'required|string|max:50',
      'part_name' => 'required|string|max:100',
      'bom_active_date' => 'required|date',
      'bom_status' => 'required|in:0,1',
    ]);

    DB::beginTransaction();

    try {
      // Generate BOM number
      $bomNo = 'BOM-' . date('Ymd') . '-' . str_pad(Bom::where('is_delete', 'N')->count() + 1, 4, '0', STR_PAD_LEFT);

      $bom = Bom::create([
        'bom_no'          => $bomNo,
        'bom_name'        => $request->bom_name,
        'part_no'         => $request->part_no,
        'part_name'       => $request->part_name,
        'part_desc'       => $request->part_desc,
        'bom_rmk'         => $request->bom_rmk,
        'bom_active_date' => $request->bom_active_date,
        'bom_status'      => $request->bom_status,
        'input_by'        => auth()->check() ? auth()->user()->name : 'System',
        'input_date'      => now(),
      ]);

      if ($request->has('detail') && is_array($request->detail)) {
        foreach ($request->detail as $row) {
          if (!empty($row['part_no'])) { // Only create if part_no is not empty
            BomDetail::create([
              'bom_id'         => $bom->bom_id,
              'part_no'        => $row['part_no'],
              'part_name'      => $row['part_name'],
              'part_desc'      => $row['part_desc'],
              'uom'            => $row['uom'],
              'bom_dtl_qty'    => $row['qty'] ?? '0',
            ]);
          }
        }
      }

      DB::commit();

      return redirect()->route('production.bom.index')->with('success', 'BOM berhasil disimpan.');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->withErrors(['error' => $e->getMessage()])->withInput();
    }
  }


  /**x`
   * Menampilkan form untuk mengedit BOM tertentu.
   *
   * @param  int  $id
   * @return \Illuminate\View\View
   */
  public function edit(string $id)
  {
    $bom = Bom::with('details')->findOrFail($id);
    return view('Production::pd-masterdata.bom.edit', compact('bom'));
  }

  /**
   * Memperbarui BOM tertentu di database.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request, string $id)
  {
    DB::beginTransaction();

    try {
      $bom = Bom::findOrFail($id);

      // Update BOM header
      $bom->update([
        'bom_name'        => $request->bom_name,
        'part_no'         => $request->part_no,
        'part_name'       => $request->part_name,
        'part_desc'       => $request->part_desc,
        'bom_rmk'         => $request->bom_rmk,
        'bom_active_date' => $request->bom_active_date,
        'bom_status'      => $request->bom_status,
        'update_by'       => auth()->check() ? auth()->user()->name : 'System',
        'update_date'     => now(),
      ]);

      // Delete existing details
      BomDetail::where('bom_id', $bom->bom_id)->delete();

      // Insert new details
      if ($request->has('detail') && is_array($request->detail)) {
        foreach ($request->detail as $row) {
          if (!empty($row['part_no'])) { // Only create if part_no is not empty
            BomDetail::create([
              'bom_id'         => $bom->bom_id,
              'part_no'        => $row['part_no'],
              'part_name'      => $row['part_name'],
              'part_desc'      => $row['part_desc'],
              'uom'            => $row['uom'],
              'bom_dtl_qty'    => $row['qty'] ?? '0',
            ]);
          }
        }
      }

      DB::commit();

      return redirect()->route('production.bom.index')->with('success', 'BOM berhasil diperbarui.');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', $e->getMessage());
    }
  }

  /**
   * Menghapus BOM tertentu dari database (soft delete).
   *
   * @param  int  $id
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(string $id)
  {
    try {
      $bom = Bom::findOrFail($id);
      $bom->update([
        'is_delete'   => 'Y',
        'delete_by'   => auth()->check() ? auth()->user()->name : 'System',
        'delete_date' => now(),
      ]);

      return response()->json(['success' => true, 'message' => 'BOM berhasil dihapus.']);
    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }

  /**
   * Menampilkan detail BOM.
   *
   * @param  int  $id
   * @return \Illuminate\View\View
   */
  public function show(string $id)
  {
    $bom =
      Bom::with('details')->findOrFail($id);
    return view('Production::pd-masterdata.bom.show', compact('bom'));
  }
}
