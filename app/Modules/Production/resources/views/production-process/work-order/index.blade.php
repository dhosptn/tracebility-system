@extends('layouts.app')

@section('title', 'Work Order')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h6 class="m-0">Work Order (WO)</h6>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item active">Work Order</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Work Order List</h3>
        <div class="card-tools">
          <a href="{{ route('wo.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add New
          </a>
        </div>
      </div>

      <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        @endif

        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover text-sm" id="wo-table" style="width: 100%">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th>WO No</th>
                <th>Process Name</th>
                <th>WO Date</th>
                <th>Production Date</th>
                <th>Item Number</th>
                <th>Item Name</th>
                <th>WO QTY</th>
                <th>WO Status</th>
                <th width="10%">Action</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  $(document).ready(function() {
    // Initialize DataTable
    var table = $('#wo-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('wo.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'wo_no', name: 'wo_no'},
            {data: 'process_name', name: 'process_name'}, // Derived from Routing
            {data: 'wo_date', name: 'wo_date'},
            {data: 'prod_date', name: 'prod_date'},
            {data: 'item_number', name: 'part_no'},
            {data: 'item_name', name: 'part_name'},
            {data: 'wo_qty', name: 'wo_qty'},
            {data: 'wo_status', name: 'wo_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[1, 'desc']],
        pageLength: 10,
        language: {
            processing: "Loading...",
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });

    // Delete handler
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var url = $(this).data('url');
        
        if (confirm('Are you sure you want to delete this Work Order?')) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        table.ajax.reload();
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error deleting Work Order');
                }
            });
        }
    });
});
</script>
@endpush