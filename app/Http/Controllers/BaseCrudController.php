<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseCrudController extends Controller
{
  protected $model; // Model class, ex: \App\Models\Customer
  protected $viewPath; // Folder blade, ex: 'customers'
  protected $route; // Route name, ex: 'customers'
  protected $columns; // Table columns for DataTables

  // Index page + DataTables
  public function index(Request $request)
  {
    if ($request->ajax()) {
      $data = $this->model::latest()->get();
      return datatables()->of($data)
        ->addIndexColumn()
        ->addColumn('action', function ($row) {
          $edit = route($this->route . '.edit', $row->id);
          $delete = route($this->route . '.destroy', $row->id);
          return "<a href='$edit' class='btn btn-sm btn-primary'>Edit</a>
<form method='POST' action='$delete' style='display:inline'>
  " . csrf_field() . method_field('DELETE') . "
  <button class='btn btn-sm btn-danger'>Delete</button>
</form>";
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    return view($this->viewPath . '.index', [
      'title' => ucfirst($this->route),
      'tableId' => $this->route . 'Table',
      'columns' => $this->columns,
      'ajaxUrl' => route($this->route . '.index')
    ]);
  }

  // Show form Add/Edit
  public function form($id = null)
  {
    $row = $id ? $this->model::findOrFail($id) : null;
    return view($this->viewPath . '.form', [
      'title' => $id ? 'Edit ' . ucfirst($this->route) : 'Add ' . ucfirst($this->route),
      'fields' => $this->columns,
      'row' => $row,
      'route' => $this->route
    ]);
  }

  // Store / Update
  public function save(Request $request, $id = null)
  {
    $data = $request->only(array_column($this->columns, 'name'));
    if ($id) {
      $this->model::findOrFail($id)->update($data);
    } else {
      $this->model::create($data);
    }
    return redirect()->route($this->route . '.index');
  }

  // Delete
  public function destroy($id)
  {
    $this->model::findOrFail($id)->delete();
    return redirect()->route($this->route . '.index');
  }
}
