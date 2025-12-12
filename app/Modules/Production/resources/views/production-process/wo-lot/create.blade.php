@extends('layouts.app')

@section('title', 'Create Lot Number')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h6 class="m-0">Create Lot Number</h6>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ route('lot_number.index') }}">Lot Number</a></li>
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
        <h3 class="card-title">Form Create Lot</h3>
        <div class="card-tools">
          <a href="{{ route('lot_number.index') }}" class="btn btn-tool" title="Back to List">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </div>

      <form action="{{ route('lot_number.store') }}" method="POST">
        @csrf
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="lot_date">Lot Date <span class="text-danger">*</span></label>
                <input type="date" name="lot_date" id="lot_date"
                  class="form-control @error('lot_date') is-invalid @enderror"
                  value="{{ old('lot_date', date('Y-m-d')) }}" required>
                @error('lot_date')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="lot_no">Lot No (Auto Generated)</label>
                <input type="text" name="lot_no" id="lot_no" class="form-control @error('lot_no') is-invalid @enderror"
                  value="{{ old('lot_no') }}" readonly placeholder="Lot Number will be generated automatically">
                <small class="text-muted">Format: LOT + YYYYMMDD + Sequence (e.g., LOT202512060001)</small>
                @error('lot_no')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="qty_per_lot">Qty Per Lot</label>
                <input type="number" step="0.01" name="qty_per_lot" id="qty_per_lot"
                  class="form-control @error('qty_per_lot') is-invalid @enderror" value="{{ old('qty_per_lot') }}"
                  placeholder="Enter quantity per lot">
                <small class="text-muted">Will be auto-calculated from Work Orders</small>
                @error('qty_per_lot')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label for="item_desc">Item Description</label>
                <input type="text" name="item_desc" id="item_desc"
                  class="form-control @error('item_desc') is-invalid @enderror" value="{{ old('item_desc') }}"
                  placeholder="Enter item description">
                @error('item_desc')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="charge_no">Charge No</label>
                <input type="text" name="charge_no" id="charge_no"
                  class="form-control @error('charge_no') is-invalid @enderror" value="{{ old('charge_no') }}"
                  placeholder="Enter charge number">
                @error('charge_no')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer text-right">
          <a href="{{ route('lot_number.index') }}" class="btn btn-default mr-2">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Lot
          </button>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  $(document).ready(function() {
        // Trigger on load if date is set
        if ($('#lot_date').val()) {
            fetchNextLotNumber($('#lot_date').val());
        }

        $('#lot_date').on('change', function() {
            fetchNextLotNumber($(this).val());
        });

        function fetchNextLotNumber(date) {
            if (!date) {
                $('#lot_no').val('');
                return;
            }

            $.ajax({
                url: "{{ route('lot_number.next') }}",
                type: "GET",
                data: { date: date },
                success: function(response) {
                    if (response.lot_no) {
                        $('#lot_no').val(response.lot_no);
                    }
                },
                error: function() {
                    console.error('Failed to fetch next lot number');
                }
            });
        }
    });
</script>