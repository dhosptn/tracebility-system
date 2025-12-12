@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Edit Lot Number</h3>
          <div class="card-tools">
            <a href="{{ route('lot_number.index') }}" class="btn btn-secondary btn-sm">
              <i class="fas fa-arrow-left"></i> Back
            </a>
          </div>
        </div>
        <div class="card-body">
          @if ($errors->any())
          <div class="alert alert-danger">
            <ul>
              @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          <form id="editLotForm" method="POST" action="{{ route('lot_number.update', $lot->lot_id) }}">
            @csrf
            @method('PUT')

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="lot_no">Lot Number *</label>
                  <input type="text" class="form-control @error('lot_no') is-invalid @enderror" id="lot_no"
                    name="lot_no" value="{{ old('lot_no', $lot->lot_no) }}" required>
                  @error('lot_no')
                  <span class="invalid-feedback">{{ $message }}</span>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="lot_date">Lot Date *</label>
                  <input type="date" class="form-control @error('lot_date') is-invalid @enderror" id="lot_date"
                    name="lot_date" value="{{ old('lot_date', $lot->lot_date->format('Y-m-d')) }}" required>
                  @error('lot_date')
                  <span class="invalid-feedback">{{ $message }}</span>
                  @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="qty_per_lot">Qty Per Lot</label>
                  <input type="text" class="form-control @error('qty_per_lot') is-invalid @enderror" id="qty_per_lot"
                    name="qty_per_lot" value="{{ old('qty_per_lot', $lot->qty_per_lot_display) }}">
                  @error('qty_per_lot')
                  <span class="invalid-feedback">{{ $message }}</span>
                  @enderror
                  <small class="text-muted">Enter number only (e.g., 110 or 110.5)</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="charge_no">Charge Number</label>
                  <input type="text" class="form-control @error('charge_no') is-invalid @enderror" id="charge_no"
                    name="charge_no" value="{{ old('charge_no', $lot->charge_no) }}">
                  @error('charge_no')
                  <span class="invalid-feedback">{{ $message }}</span>
                  @enderror
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label for="item_desc">Item Description</label>
                  <textarea class="form-control @error('item_desc') is-invalid @enderror" id="item_desc"
                    name="item_desc" rows="2">{{ old('item_desc', $lot->item_desc) }}</textarea>
                  @error('item_desc')
                  <span class="invalid-feedback">{{ $message }}</span>
                  @enderror
                </div>
              </div>
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> Update Lot
              </button>
              <a href="{{ route('lot_number.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
              </a>
            </div>
          </form>
        </div>
        <div class="card-footer">
          <small class="text-muted">
            <strong>Created by:</strong> {{ $lot->lot_create_by ?? 'System' }}
            <strong>on</strong> {{ $lot->created_at->format('d-m-Y H:i') }}
            @if($lot->updated_at != $lot->created_at)
            | <strong>Last updated:</strong> {{ $lot->updated_at->format('d-m-Y H:i') }}
            @endif
          </small>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function() {
    // Format input qty_per_lot saat blur (kehilangan fokus)
    $('#qty_per_lot').on('blur', function() {
        var value = $(this).val().trim();
        
        if (value === '') {
            return;
        }
        
        // Hapus semua karakter non-digit kecuali titik
        value = value.replace(/[^\d.]/g, '');
        
        // Hapus titik ganda jika ada
        var parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Parse sebagai float
        var num = parseFloat(value);
        
        if (!isNaN(num)) {
            // Cek jika bilangan bulat
            if (Math.floor(num) === num) {
                $(this).val(num.toString());
            } else {
                // Format dengan 2 desimal
                $(this).val(num.toFixed(2));
            }
        }
    });
    
    // AJAX form submission dengan konfirmasi
    $('#editLotForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to update this lot?')) {
            return false;
        }
        
        var form = $(this);
        var url = form.attr('action');
        var formData = form.serialize();
        
        // Show loading state
        var submitBtn = $('#submitBtn');
        var originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    // AJAX response
                    alert('Lot updated successfully!');
                    
                    // Trigger custom event untuk refresh di index page
                    var lotId = '{{ $lot->lot_id }}';
                    $(document).trigger('lotUpdated', [lotId]);
                    
                    // Redirect ke index
                    window.location.href = "{{ route('lot_number.index') }}";
                } else {
                    // Non-AJAX response (regular form submit fallback)
                    alert('Lot updated successfully!');
                    window.location.href = "{{ route('lot_number.index') }}";
                }
            },
            error: function(xhr) {
                // Reset button state
                submitBtn.prop('disabled', false).html(originalText);
                
                if (xhr.status === 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    var errorMessage = '';
                    
                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '\n';
                    });
                    
                    alert('Validation Error:\n' + errorMessage);
                } else {
                    // Other errors - fallback to regular form submission
                    alert('AJAX update failed. Trying regular form submission...');
                    
                    // Submit form secara tradisional
                    form.off('submit').submit();
                }
            }
        });
        
        return false;
    });
    
    // Fallback jika AJAX tidak bekerja
    setTimeout(function() {
        if (!$('#editLotForm').data('ajax-setup')) {
            // Hapus AJAX handler dan gunakan form submission biasa
            $('#editLotForm').off('submit');
        }
    }, 1000);
});
</script>
@endsection