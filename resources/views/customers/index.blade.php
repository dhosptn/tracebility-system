@extends('layouts.app')

@section('content')
<div class="container">
  <a href="{{ route('customers.create') }}" class="btn btn-success mb-2">Add Customer</a>
  <table class="table table-bordered" id="customerTable">
    <thead>
      <tr>
        <th>No</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Action</th>
      </tr>
    </thead>
  </table>
</div>
@endsection

@push('scripts')
<script>
  $(function() {
    $('#customerTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('customers.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'action', name: 'action', orderable:false, searchable:false},
        ]
    });
});
</script>
@endpush