@extends('layouts.master')
@section('title', $title)
@section('content')
<a href="{{ route($route.'.create') }}" class="btn btn-success mb-2">Add {{ $title }}</a>
<table class="table table-bordered" id="{{ $tableId }}">
  <thead>
    <tr>
      <th>No</th>
      @foreach($columns as $col)
      <th>{{ $col['label'] }}</th>
      @endforeach
      <th>Action</th>
    </tr>
  </thead>
</table>
@endsection

@push('scripts')
<script>
  $(function(){
    $('#{{ $tableId }}').DataTable({
        processing:true,
        serverSide:true,
        ajax: "{{ $ajaxUrl }}",
        columns:[
            {data:'DT_RowIndex',name:'DT_RowIndex'},
            @foreach($columns as $col)
            {data:'{{ $col['name'] }}',name:'{{ $col['name'] }}'},
            @endforeach
            {data:'action',name:'action',orderable:false,searchable:false}
        ]
    });
});
</script>
@endpush