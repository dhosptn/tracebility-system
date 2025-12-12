@extends('layouts.app')

@section('title', 'Item Category')

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Item Categories</h3>
      <div class="card-tools">
        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-add">
          <i class="fas fa-plus"></i> Add Category
        </button>
      </div>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-striped" id="category-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Name</th>
            <th>Description</th>
            <th>Type</th>
            <th>Action</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="modal-add">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="form-add">
        @csrf
        <div class="modal-header">
          <h4 class="modal-title">Add Category</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Category Name</label>
            <input type="text" name="item_cat_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="item_cat_desc" class="form-control"></textarea>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="item_cat_type" class="form-control">
              <option value="Inventory">Inventory</option>
              <option value="Non-Inventory">Non-Inventory</option>
            </select>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  $(function() {
    var table = $('#category-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('itemcategory.data') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'item_cat_name', name: 'item_cat_name'},
            {data: 'item_cat_desc', name: 'item_cat_desc'},
            {data: 'transaction_status', name: 'transaction_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    $('#form-add').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('itemcategory.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                $('#modal-add').modal('hide');
                table.ajax.reload();
                $('#form-add')[0].reset();
                alert('Category added successfully');
            },
            error: function(xhr) {
                alert('Error adding category');
            }
        });
    });
});
</script>
@endpush