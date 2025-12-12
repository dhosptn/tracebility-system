@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <!-- Back Button -->
  <div class="mb-3">
    <a href="{{ route('setting-process.index') }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left"></i> Back to Setting Process List
    </a>
    <a href="{{ route('setting-process.edit', $routing->routing_id) }}" class="btn btn-warning">
      <i class="fas fa-edit"></i> Edit
    </a>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Setting Process Detail</h4>
    </div>

    <div class="card-body">
      <!-- Setting Process Information -->
      <h5 class="border-bottom pb-2 mb-3">General Information</h5>
      <div class="row mb-4">
        <div class="col-md-3">
          <label class="fw-bold">Setting Process Name:</label>
          <p>{{ $routing->routing_name }}</p>
        </div>

        <div class="col-md-3">
          <label class="fw-bold">Description:</label>
          <p>{{ $routing->routing_rmk ?? '-' }}</p>
        </div>

        <div class="col-md-3">
          <label class="fw-bold">Active Date:</label>
          <p>{{ $routing->routing_active_date ? $routing->routing_active_date->format('d-m-Y') : '-' }}</p>
        </div>

        <div class="col-md-3">
          <label class="fw-bold">Status:</label>
          <p>
            @if($routing->routing_status == 1)
            <span class="badge bg-success">Active</span>
            @else
            <span class="badge bg-secondary">Inactive</span>
            @endif
          </p>
        </div>
      </div>

      <!-- Part Information -->
      <h5 class="border-bottom pb-2 mb-3">Part Information</h5>
      <div class="row mb-4">
        <div class="col-md-3">
          <label class="fw-bold">Part Number:</label>
          <p>{{ $routing->part_no ?? '-' }}</p>
        </div>

        <div class="col-md-5">
          <label class="fw-bold">Part Name:</label>
          <p>{{ $routing->part_name ?? '-' }}</p>
        </div>

        <div class="col-md-4">
          <label class="fw-bold">UOM:</label>
          <p>{{ $routing->part_desc ?? '-' }}</p>
        </div>
      </div>

      <!-- Step Process Section -->
      <h5 class="border-bottom pb-2 mb-3">Step Process</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th width="50">No</th>
              <th>Process Name</th>
              <th>Process Description</th>
              <th width="180">Cycle Time (seconds)</th>
            </tr>
          </thead>
          <tbody>
            @forelse($routing->details as $index => $detail)
            <tr>
              <td class="text-center">{{ $index + 1 }}</td>
              <td>{{ $detail->process_name }}</td>
              <td>{{ $detail->process_desc ?? '-' }}</td>
              <td class="text-center">{{ $detail->cycle_time_second }}</td>
            </tr>
            @empty
            <tr>
              <td colspan="4" class="text-center text-muted">No process steps found</td>
            </tr>
            @endforelse
          </tbody>
          @if($routing->details->count() > 0)
          <tfoot class="table-light">
            <tr>
              <th colspan="3" class="text-end">Total Cycle Time:</th>
              <th class="text-center">{{ $routing->details->sum('cycle_time_second') }} seconds</th>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>

      <!-- Audit Trail -->
      <h5 class="border-bottom pb-2 mb-3 mt-4">Audit Trail</h5>
      <div class="row">
        <div class="col-md-6">
          <label class="fw-bold">Created By:</label>
          <p>{{ $routing->input_by ?? '-' }}
            @if($routing->input_date)
            <small class="text-muted">on {{ $routing->input_date->format('d-m-Y H:i') }}</small>
            @endif
          </p>
        </div>

        <div class="col-md-6">
          <label class="fw-bold">Last Modified By:</label>
          <p>{{ $routing->edit_by ?? '-' }}
            @if($routing->edit_date)
            <small class="text-muted">on {{ $routing->edit_date->format('d-m-Y H:i') }}</small>
            @endif
          </p>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <div class="d-flex justify-content-between">
        <a href="{{ route('setting-process.index') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="{{ route('setting-process.edit', $routing->routing_id) }}" class="btn btn-warning">
          <i class="fas fa-edit"></i> Edit Routing
        </a>
      </div>
    </div>
  </div>
</div>
@endsection