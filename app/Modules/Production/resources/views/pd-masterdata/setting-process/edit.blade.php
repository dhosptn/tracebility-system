@extends('layouts.app')

@section('title', 'Edit Settings Process')

@section('content')
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Form Edit Settings Process</h3>
        <div class="card-tools">
          <a href="{{ route('setting-process.index') }}" class="btn btn-tool" title="Back to List">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </div>

      <form action="{{ route('setting-process.update', $routing->routing_id) }}" method="POST" id="routing-form">
        @csrf
        @method('PUT')
        <div class="card-body">
          <!-- Header Information -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="routing_name">Setting Process Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('routing_name') is-invalid @enderror" id="routing_name"
                  name="routing_name" placeholder="Enter Process Name"
                  value="{{ old('routing_name', $routing->routing_name) }}" required>
                @error('routing_name')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="routing_rmk">Description</label>
                <input type="text" class="form-control @error('routing_rmk') is-invalid @enderror" id="routing_rmk"
                  name="routing_rmk" placeholder="Enter Description"
                  value="{{ old('routing_rmk', $routing->routing_rmk) }}">
                @error('routing_rmk')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="routing_active_date">Active Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('routing_active_date') is-invalid @enderror"
                  id="routing_active_date" name="routing_active_date"
                  value="{{ old('routing_active_date', $routing->routing_active_date ? $routing->routing_active_date->format('Y-m-d') : '') }}"
                  required>
                @error('routing_active_date')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="routing_status">Status <span class="text-danger">*</span></label>
                <select class="form-control @error('routing_status') is-invalid @enderror" id="routing_status"
                  name="routing_status" required>
                  <option value="1" {{ old('routing_status', $routing->routing_status) == '1' ? 'selected' : '' }}>
                    Active</option>
                  <option value="0" {{ old('routing_status', $routing->routing_status) == '0' ? 'selected' : '' }}>
                    Inactive</option>
                </select>
                @error('routing_status')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <hr>
          <h5 class="mb-3 text-muted">Product Information</h5>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="part_no">Part Number</label>
                <div class="input-group">
                  <input type="text" class="form-control @error('part_no') is-invalid @enderror" id="part_no"
                    name="part_no" placeholder="Auto Fill" value="{{ old('part_no', $routing->part_no) }}" readonly>
                  <div class="input-group-append">
                    <button class="btn btn-info" type="button" id="btn-select-part">
                      <i class="fas fa-search"></i> Select
                    </button>
                  </div>
                </div>
                @error('part_no')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="part_name">Product Name</label>
                <input type="text" class="form-control @error('part_name') is-invalid @enderror" id="part_name"
                  name="part_name" placeholder="Product Name" value="{{ old('part_name', $routing->part_name) }}"
                  readonly>
                @error('part_name')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="part_desc">UOM</label>
                <input type="text" class="form-control @error('part_desc') is-invalid @enderror" id="part_desc"
                  name="part_desc" placeholder="UOM" value="{{ old('part_desc', $routing->part_desc) }}" readonly>
                @error('part_desc')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <hr>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-muted">Step Process</h5>
            <button type="button" class="btn btn-success btn-sm" id="btn-add-row">
              <i class="fas fa-plus"></i> Add Process
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped text-sm" id="process-table">
              <thead class="bg-light">
                <tr>
                  <th width="5%" class="text-center">No</th>
                  <th width="30%">Process Name</th>
                  <th width="35%">Process Description</th>
                  <th width="20%">Cycle Time (s)</th>
                  <th width="10%" class="text-center">Action</th>
                </tr>
              </thead>
              <tbody id="process-tbody">
                @foreach ($routing->details as $index => $detail)
                <tr>
                  <td class="text-center">{{ $index + 1 }}</td>
                  <td>
                    <select class="form-control form-control-sm process-select" name="process_id[]" required>
                      <option value="">-- Select Process --</option>
                      @foreach ($processes as $process)
                      <option value="{{ $process->proces_id }}" data-name="{{ $process->process_name }}"
                        data-desc="{{ $process->process_desc }}" {{ $detail->process_id == $process->proces_id ?
                        'selected' : '' }}>
                        {{ $process->process_name }}
                      </option>
                      @endforeach
                    </select>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm process-desc" name="process_desc[]"
                      value="{{ $detail->process_desc }}" readonly>
                  </td>
                  <td>
                    <input type="number" class="form-control form-control-sm" name="cycle_time_second[]"
                      value="{{ $detail->cycle_time_second }}" min="0" required>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row" {{ $index==0 ? 'disabled' : ''
                      }}>
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-right">
          <a href="{{ route('setting-process.index') }}" class="btn btn-default mr-2">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update Settings
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- Modal untuk pilih produk -->
<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Product</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-hover table-striped text-sm" id="product-table" style="width: 100%;">
          <thead class="table-light">
            <tr>
              <th width="5%">No</th>
              <th width="25%">Product Name</th>
              <th width="10%">UOM</th>
              <th width="10%">Model</th>
              <th width="15%">Category</th>
              <th width="25%">Description</th>
              <th width="10%">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($items as $index => $item)
            <tr>
              <td class="text-center">{{ $index + 1 }}</td>
              <td>{{ $item->item_name }}</td>
              <td>{{ $item->uom->uom_code ?? '-' }}</td>
              <td>{{ $item->model ?? '-' }}</td>
              <td>{{ $item->category->item_cat_name ?? '-' }}</td>
              <td>{{ $item->item_description ?? '-' }}</td>
              <td class="text-center">
                <button type="button" class="btn btn-xs btn-primary select-product"
                  data-part-no="{{ $item->item_number }}" data-part-name="{{ $item->item_name }}"
                  data-part-desc="{{ $item->uom->uom_code ?? '' }}">
                  <i class="fas fa-check"></i> Select
                </button>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  $(document).ready(function() {
                let rowCount = {{ count($routing->details) }};

                // Add new row
                $('#btn-add-row').click(function() {
                    rowCount++;
                    let newRow = `
            <tr>
                <td class="text-center">${rowCount}</td>
                <td>
                    <select class="form-control form-control-sm process-select" name="process_id[]" required>
                        <option value="">-- Select Process --</option>
                        @foreach ($processes as $process)
                        <option value="{{ $process->proces_id }}" 
                                data-name="{{ $process->process_name }}"
                                data-desc="{{ $process->process_desc }}">
                            {{ $process->process_name }}
                        </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm process-desc" 
                           name="process_desc[]" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           name="cycle_time_second[]" 
                           value="0" min="0" required>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
                    $('#process-tbody').append(newRow);
                    updateRowNumbers();
                });

                // Remove row
                $(document).on('click', '.btn-remove-row', function() {
                    if ($('#process-tbody tr').length > 1) {
                        $(this).closest('tr').remove();
                        rowCount--;
                        updateRowNumbers();
                    }
                });

                // Update row numbers
                function updateRowNumbers() {
                    $('#process-tbody tr').each(function(index) {
                        $(this).find('td:first').text(index + 1);
                        // Enable/disable remove button
                        if (index === 0) {
                            $(this).find('.btn-remove-row').prop('disabled', true);
                        } else {
                            $(this).find('.btn-remove-row').prop('disabled', false);
                        }
                    });
                }

                // Process selection change
                $(document).on('change', '.process-select', function() {
                    let selected = $(this).find(':selected');
                    let desc = selected.data('desc') || '';
                    $(this).closest('tr').find('.process-desc').val(desc);
                });

                // Product modal
                $('#btn-select-part').click(function() {
                    $('#productModal').modal('show');
                });

                // Select product
                $(document).on('click', '.select-product', function() {
                    $('#part_no').val($(this).data('part-no'));
                    $('#part_name').val($(this).data('part-name'));
                    $('#part_desc').val($(this).data('part-desc'));
                    $('#productModal').modal('hide');
                });

                // Initialize DataTable for product modal
                $('#product-table').DataTable({
                    pageLength: 10,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                    }
                });
            });
</script>
@endpush
@endsection