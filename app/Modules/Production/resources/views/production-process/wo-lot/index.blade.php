@extends('layouts.app')

@section('title', 'Lot Number')

@section('content')
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Lot Number List</h3>
        <div class="card-tools">
          <a href="{{ route('production.lot_number.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Lot
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
          <table id="lotTable" class="table table-bordered table-striped table-hover text-sm" style="width:100%">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th>Lot No</th>
                <th>Lot Date</th>
                <th>Qty Per Lot</th>
                <th>Item Desc</th>
                <th>Charge No</th>
                <th>Created By</th>
                <th width="10%">Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Include Modals -->
@include('Production::production-process.wo-lot.modals.lot-report')

@endsection

<!-- Include Scripts -->
@push('scripts')
@include('Production::production-process.wo-lot.script.index')
@endpush