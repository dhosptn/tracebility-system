@extends('layouts.app')

@section('title', 'Edit Work Order')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h6 class="m-0">Edit Work Order</h6>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
          <li class="breadcrumb-item"><a href="{{ route('wo.index') }}">Work Order</a></li>
          <li class="breadcrumb-item active">Edit</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Form Edit Work Order</h3>
        <div class="card-tools">
          <a href="{{ route('wo.index') }}" class="btn btn-tool" title="Back to List">
            <i class="fas fa-times"></i> Back to Work Order List
          </a>
        </div>
      </div>

      <form action="{{ route('wo.update', $wo->wo_id) }}" method="POST" id="wo-form">
        @csrf
        @method('PUT')
        <div class="card-body">
          @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          @endif

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="wo_no">WO No <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="wo_no" name="wo_no" value="{{ $wo->wo_no }}" readonly>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="wo_date">WO Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('wo_date') is-invalid @enderror" id="wo_date"
                  name="wo_date" value="{{ old('wo_date', $wo->wo_date ? $wo->wo_date->format('Y-m-d') : '') }}"
                  required>
                @error('wo_date')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="prod_date">Production Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('prod_date') is-invalid @enderror" id="prod_date"
                  name="prod_date" value="{{ old('prod_date', $wo->prod_date ? $wo->prod_date->format('Y-m-d') : '') }}"
                  required>
                @error('prod_date')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="wo_qty">WO Qty <span class="text-danger">*</span></label>
                <input type="number" step="0.01" class="form-control @error('wo_qty') is-invalid @enderror" id="wo_qty"
                  name="wo_qty" placeholder="WO Qty" value="{{ old('wo_qty', $wo->wo_qty) }}" required>
                @error('wo_qty')
                <span class="invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="part_no">Part Number <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="text" class="form-control @error('part_no') is-invalid @enderror" id="part_no"
                    name="part_no" placeholder="Otomatis terisi" value="{{ old('part_no', $wo->part_no) }}" readonly>
                  <div class="input-group-append">
                    <button class="btn btn-default" type="button" id="btn-select-part">
                      <i class="fas fa-search"></i> Pilih Produk
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
                <label for="part_name">Nama Produk</label>
                <input type="text" class="form-control" id="part_name" name="part_name"
                  placeholder="Masukkan nama produk" value="{{ old('part_name', $wo->part_name) }}" readonly>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="uom">UOM (Unit of Measure)</label>
                <input type="text" class="form-control" id="uom" name="uom" placeholder="Otomatis terisi"
                  value="{{ old('uom', $wo->uom_id) }}" readonly>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="lot_id">Lot Number</label>
                <select class="form-control" id="lot_id" name="lot_id">
                  <option value="">Select Lot (Optional)</option>
                  @foreach($lots as $lot)
                  <option value="{{ $lot->lot_id }}" {{ old('lot_id', $wo->lot_id) == $lot->lot_id ? 'selected' : ''
                    }}>{{ $lot->lot_no }}</option>
                  @endforeach
                </select>
                @error('lot_id')
                <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="wo_rmk">WO Remarks</label>
                <input type="text" class="form-control" id="wo_rmk" name="wo_rmk" placeholder="Remarks"
                  value="{{ old('wo_rmk', $wo->wo_rmk) }}">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="wo_status">Status <span class="text-danger">*</span></label>
                <select class="form-control" id="wo_status" name="wo_status">
                  <option value="Release" {{ $wo->wo_status == 'Release' ? 'selected' : '' }}>Release</option>
                  <option value="Draft" {{ $wo->wo_status == 'Draft' ? 'selected' : '' }}>Draft</option>
                  <option value="On Process" {{ $wo->wo_status == 'On Process' ? 'selected' : '' }}>On Process</option>
                  <option value="Built" {{ $wo->wo_status == 'Built' ? 'selected' : '' }}>Built</option>
                </select>
              </div>
            </div>
          </div>

        </div>
        <div class="card-footer text-right">
          <a href="{{ route('wo.index') }}" class="btn btn-default mr-2">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update WO
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
@endsection

@push('scripts')
<script>
  $(document).ready(function() {
        // Product modal
        $('#btn-select-part').click(function() {
            $('#productModal').modal('show');
        });

        // Initialize DataTable for product modal
        $('#product-table').DataTable({
            pageLength: 10,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
            }
        });

        // Select product
        $(document).on('click', '.select-product', function() {
            let partNo = $(this).data('part-no');
            let partName = $(this).data('part-name');
            let uom = $(this).data('part-desc');

            $('#part_no').val(partNo);
            $('#part_name').val(partName);
            $('#uom').val(uom);
            $('#productModal').modal('hide');
        });
    });
</script>
@endpush