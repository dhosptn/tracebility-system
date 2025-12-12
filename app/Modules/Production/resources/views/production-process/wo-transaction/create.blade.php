@extends('layouts.app')

@section('title', 'Create Work Order Transaction')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h6 class="m-0">Create Work Order Transaction</h6>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ route('wo_transaction.index') }}">Work Order Transaction</a></li>
          <li class="breadcrumb-item active">Create</li>
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
          <i class="fas fa-plus mr-1"></i>
          New Work Order Transaction
        </h3>
      </div>
      <form action="{{ route('wo_transaction.store') }}" method="POST" id="wo-transaction-form">
        @csrf
        <div class="card-body">

          <!-- Row 1: Trx No, WO No, WO Qty, Process -->
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="trx_no">Trx No <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('trx_no') is-invalid @enderror" id="trx_no" name="trx_no"
                  value="{{ old('trx_no', $autoTrxNo) }}" readonly>
                @error('trx_no')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="wo_no">WO No. <span class="text-danger">*</span></label>
                <select class="form-control select2 @error('wo_no') is-invalid @enderror" id="wo_no" name="wo_no"
                  style="width: 100%;">
                  <option value="">Select WO No</option>
                  @foreach($workOrders as $wo)
                  <option value="{{ $wo->wo_id }}" data-no="{{ $wo->wo_no }}">
                    {{ $wo->wo_no }} - {{ $wo->part_no }}
                  </option>
                  @endforeach
                </select>
                @error('wo_no')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="wo_qty">WO Qty <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="wo_qty" name="wo_qty" readonly placeholder="WO Qty">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="process_id">Process <span class="text-danger">*</span></label>
                <select class="form-control select2 @error('process_id') is-invalid @enderror" id="process_id"
                  name="process_id" style="width: 100%;">
                  <option value="">Select Process</option>
                  {{-- Will be populated via AJAX --}}
                </select>
                @error('process_id')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <!-- Row 2: Supervisor, Operator, Machine, Shift -->
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="supervisor">Supervisor</label>
                <select class="form-control select2" id="supervisor" name="supervisor" style="width: 100%;">
                  <option value="">Select Supervisor</option>
                  @foreach($users as $user)
                  <option value="{{ $user->name }}">{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="operator">Operator</label>
                <select class="form-control select2" id="operator" name="operator" style="width: 100%;">
                  <option value="">Select Operator</option>
                  @foreach($users as $user)
                  <option value="{{ $user->name }}">{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="machine">Machine</label>
                <select class="form-control select2" id="machine" name="machine" style="width: 100%;">
                  <option value="">Select Machine</option>
                  @foreach($machines as $machine)
                  <option value="{{ $machine->machine_name }}">{{ $machine->machine_name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="shift">Shift <span class="text-danger">*</span></label>
                <select class="form-control" id="shift" name="shift">
                  <option value="">Select Shift</option>
                  @foreach($shifts as $shift)
                  <option value="{{ $shift }}">Shift {{ $shift }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <!-- Row 3: Start Date, Start Time, End Date, End Time -->
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="start_date">Start Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date"
                  name="start_date" value="{{ old('start_date') }}">
                @error('start_date')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="start_time">Start Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control @error('start_time') is-invalid @enderror" id="start_time"
                  name="start_time" value="{{ old('start_time') }}">
                @error('start_time')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="end_date">End Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                  name="end_date" value="{{ old('end_date') }}">
                @error('end_date')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="end_time">End Time <span class="text-danger">*</span></label>
                <input type="time" class="form-control @error('end_time') is-invalid @enderror" id="end_time"
                  name="end_time" value="{{ old('end_time') }}">
                @error('end_time')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <!-- Row 4: Remain Qty, Good Qty, NG Qty, Downtime -->
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="remain_qty">Remain Qty</label>
                <input type="number" class="form-control @error('remain_qty') is-invalid @enderror" id="remain_qty"
                  name="remain_qty" value="{{ old('remain_qty') }}" placeholder="Remain Qty" readonly>
                @error('remain_qty')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="good_qty">Good Qty <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('good_qty') is-invalid @enderror" id="good_qty"
                  name="good_qty" value="{{ old('good_qty') }}" placeholder="Qty" min="0">
                @error('good_qty')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="ng_qty">NG Qty <span class="text-danger">*</span></label>
                <input type="number" class="form-control @error('ng_qty') is-invalid @enderror" id="ng_qty"
                  name="ng_qty" value="{{ old('ng_qty') }}" placeholder="Qty" min="0">
                @error('ng_qty')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="downtime">Downtime</label>
                <input type="text" class="form-control @error('downtime') is-invalid @enderror" id="downtime"
                  name="downtime" value="{{ old('downtime') }}" placeholder="HH:MM">
                @error('downtime')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

        </div>
        <div class="card-footer text-right">
          <a href="{{ route('wo_transaction.index') }}" class="btn btn-default mr-2">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Transaction
          </button>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection

@push('styles')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" />
@endpush

@push('scripts')
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function () {
    // Initialize Select2
    $('.select2').select2({
      theme: 'bootstrap4'
    });

    // When WO No is selected, fetch WO details
    $('#wo_no').on('change', function () {
      var woId = $(this).val();
      var woNo = $(this).find(':selected').data('no');
      
      if (woNo) {
        $.ajax({
          url: "{{ route('wo_transaction.wo-details') }}",
          type: 'GET',
          data: { wo_no: woNo },
          success: function (response) {
            $('#wo_qty').val(response.wo_qty);
            $('#remain_qty').val(response.remain_qty);
            
            // Populate Process
            var processSelect = $('#process_id');
            processSelect.empty().append('<option value="">Select Process</option>');
            if (response.processes && response.processes.length > 0) {
              $.each(response.processes, function (index, process) {
                processSelect.append('<option value="' + process.process_id + '">' + process.process_name + '</option>');
              });
            }
            processSelect.trigger('change');
          },
          error: function () {
            alert('Failed to fetch WO Details');
          }
        });
      } else {
        $('#wo_qty').val('');
        $('#remain_qty').val('');
        $('#process_id').empty().append('<option value="">Select Process</option>');
      }
    });

    // Form validation
    $('#wo-transaction-form').on('submit', function (e) {
      var isValid = true;
      var errorMessage = '';

      if (!$('#wo_no').val()) {
        isValid = false;
        errorMessage += 'Please select WO No.\n';
      }

      if (!$('#process_id').val()) {
        isValid = false;
        errorMessage += 'Please select Process.\n';
      }

      if (!$('#shift').val()) {
        isValid = false;
        errorMessage += 'Please select Shift.\n';
      }

      if (!$('#start_date').val() || !$('#start_time').val()) {
        isValid = false;
        errorMessage += 'Please enter Start Date and Time.\n';
      }

      if (!$('#end_date').val() || !$('#end_time').val()) {
        isValid = false;
        errorMessage += 'Please enter End Date and Time.\n';
      }

      if (!$('#good_qty').val() || !$('#ng_qty').val()) {
        isValid = false;
        errorMessage += 'Please enter Good Qty and NG Qty.\n';
      }

      if (!isValid) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Validation Error',
          text: errorMessage
        });
      }
    });
  });
</script>
@endpush