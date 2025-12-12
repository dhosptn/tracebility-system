@extends('layouts.app')

@section('title', 'Work Order Transaction')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h6 class="m-0">Work Order Transaction</h6>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item active">Work Order Transaction</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">
          <i class="fas fa-list mr-1"></i>
          Work Order Transaction List
        </h3>
        <div class="card-tools">
          <a href="{{ route('production.wo-transaction.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Create New Transaction
          </a>
        </div>
      </div>
      <div class="card-body">
        <table class="table table-bordered table-striped table-hover text-sm" id="wo-transaction-table"
          style="width: 100%">
          <thead>
            <tr>
              <th width="5%">No</th>
              <th>WO No</th>
              <th>Item</th>
              <th>Process Name</th>
              <th>Total Qty OK</th>
              <th>Total Qty NG</th>
              <th>Prod. Time</th>
              <th>Downtime</th>
              <th>OEE</th>
              <th>WO Date</th>
              <th>Production Date</th>
              <th width="10%">Action</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  $(document).ready(function () {
    var table = $('#wo-transaction-table').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('production.wo-transaction.index') }}",
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'wo_no', name: 'wo_no' },
        { data: 'item', name: 'item' },
        { data: 'process_name', name: 'process_name' },
        { data: 'good_qty', name: 'good_qty', className: 'text-right', render: function(data) { return parseFloat(data); } },
        { data: 'ng_qty', name: 'ng_qty', className: 'text-right', render: function(data) { return parseFloat(data); } },
        { data: 'prod_time', name: 'prod_time', className: 'text-center' },
        { data: 'downtime', name: 'downtime', className: 'text-center' },
        { data: 'oee', name: 'oee', className: 'text-center' },
        { data: 'wo_date', name: 'wo_date', className: 'text-center' },
        { data: 'prod_date', name: 'prod_date', className: 'text-center' },
        { data: 'action', name: 'action', orderable: false, searchable: false }
      ],
      responsive: true,
      autoWidth: false,
      language: {
        processing: "Loading...",
      }
    });

    // Delete button handler
    $('#wo-transaction-table').on('click', '.delete-btn', function () {
      var id = $(this).data('id');
      var url = $(this).data('url');

      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: url,
            type: 'DELETE',
            data: {
              _token: '{{ csrf_token() }}'
            },
            success: function (response) {
              Swal.fire(
                'Deleted!',
                response.message,
                'success'
              );
              table.ajax.reload();
            },
            error: function (xhr) {
              Swal.fire(
                'Error!',
                'Failed to delete transaction.',
                'error'
              );
            }
          });
        }
      });
    });

    // Success/Error messages
    @if(session('success'))
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: '{{ session('success') }}',
      timer: 3000
    });
    @endif

    @if(session('error'))
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '{{ session('error') }}',
      timer: 3000
    });
    @endif
  });
</script>
@endpush