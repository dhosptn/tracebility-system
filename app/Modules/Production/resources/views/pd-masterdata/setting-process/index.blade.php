@extends('layouts.app')

@section('title', 'Settings Process')

@section('content')
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Settings Process List</h3>
        <div class="card-tools">
          <a href="{{ route('production.setting-process.create') }}" class="btn btn-primary btn-sm">
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
          <table class="table table-bordered table-striped table-hover text-sm" id="routing-table" style="width: 100%">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th>Routing Name</th>
                <th>Description</th>
                <th width="15%">Active Date</th>
                <th width="10%">Status</th>
                <th width="15%">Action</th>
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
    var table = $('#routing-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('production.setting-process.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'routing_name_link', name: 'routing_name'},
            {data: 'routing_rmk', name: 'routing_rmk'},
            {data: 'routing_active_date', name: 'routing_active_date'},
            {data: 'routing_status', name: 'routing_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[1, 'asc']],
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
        
        if (confirm('Are you sure you want to delete this routing?')) {
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
                    alert('Error deleting routing');
                }
            });
        }
    });
});
</script>
@endpush