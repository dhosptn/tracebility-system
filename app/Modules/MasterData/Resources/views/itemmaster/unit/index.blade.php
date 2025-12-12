@extends('layouts.app')
@section('title', 'UOM (Unit)')

@section('content')
<div class="card">
  <div class="card-header">
    <a href="{{ route('master-data.unit.create') }}" class="btn btn-primary btn-sm btn-mr float-left">
      <i class="fas fa-plus mr-1"></i> Add New
    </a>
  </div>

  <div class="card-body">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
    @endif

    <table id="unitTable" class="table table-bordered table-striped text-sm">
      <thead>
        <tr>
          <th width="5%">No</th>
          <th width="15%">Code</th>
          <th>Description</th>
          <th>Updated</th>
          <th width="15%">Action</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
  $(document).ready(function(){
    // Setup CSRF Token for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable
    let table = $('#unitTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("master-data.unit.data") }}',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'uom_code', name: 'uom_code' },
            { data: 'uom_desc', name: 'uom_desc' },
            { data: 'updated_info', name: 'updated_info' },
            { 
                data: 'action', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row, meta) {
                    // Button Edit dan Delete
                    return `
                        <a href="{{ url('master-data/units') }}/${row.id}/edit" 
                           class="btn btn-sm btn-warning btn-edit" 
                           title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger delete-btn" 
                                data-id="${row.id}" 
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    // Delete Button Click
    $(document).on('click', '.delete-btn', function(){
        let id = $(this).data('id');
        if(confirm('Are you sure you want to delete this unit?')){
            $.ajax({
                url: '{{ url("master-data/units") }}/' + id,
                type: 'DELETE',
                success: function(response){
                    table.ajax.reload();
                    alert(response.message);
                },
                error: function(xhr){
                    alert('Error deleting unit');
                }
            });
        }
    });

  });
</script>
@endpush