@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
<div class="container-fluid">
  <div class="card card-warning card-outline">
    <div class="card-header">
      <h6 class="card-title">Edit Item: {{ $item->item_number }}</h6>
    </div>

    <form action="{{ route('itemmaster.update', $item->item_id) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="card-body">
        <!-- Stock Type (Hidden) -->
        <input type="hidden" name="stock_type" value="{{ $item->stock_type }}">

        <!-- Row 1: SKU/Part No & Part Name -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="item_number">SKU / Part No <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('item_number') is-invalid @enderror" id="item_number"
                name="item_number" value="{{ old('item_number', $item->item_number) }}" required>
              @error('item_number')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="item_name">Part Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('item_name') is-invalid @enderror" id="item_name"
                name="item_name" value="{{ old('item_name', $item->item_name) }}" required>
              @error('item_name')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 2: Description & Model -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="item_description">Part Description</label>
              <textarea class="form-control @error('item_description') is-invalid @enderror" id="item_description"
                name="item_description" rows="2">{{ old('item_description', $item->item_description) }}</textarea>
              @error('item_description')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="model">Model</label>
              <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model"
                value="{{ old('model', $item->model) }}">
              @error('model')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 3: Lot Item & UOM -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="lot_item">Lot Item</label>
              <input type="text" class="form-control @error('lot_item') is-invalid @enderror" id="lot_item"
                name="lot_item" value="{{ old('lot_item', $item->lot_item) }}">
              @error('lot_item')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="uom_id">Unit of Measurement (UOM) <span class="text-danger">*</span></label>
              <select class="form-control @error('uom_id') is-invalid @enderror" id="uom_id" name="uom_id" required>
                <option value="">-- Select UOM --</option>
                @foreach($uoms as $uom)
                <option value="{{ $uom->uom_id }}" {{ old('uom_id', $item->uom_id) == $uom->uom_id ? 'selected' : '' }}>
                  {{ $uom->uom_code }} - {{ $uom->uom_desc }}
                </option>
                @endforeach
              </select>
              @error('uom_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 4: Second UOM & Volume M3 -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="second_uom">Second UOM</label>
              <select class="form-control @error('second_uom') is-invalid @enderror" id="second_uom" name="second_uom">
                <option value="">-- Select Second UOM --</option>
                @foreach($uoms as $uom)
                <option value="{{ $uom->uom_id }}" {{ old('second_uom', $item->second_uom) == $uom->uom_id ? 'selected'
                  : '' }}>
                  {{ $uom->uom_code }} - {{ $uom->uom_desc }}
                </option>
                @endforeach
              </select>
              @error('second_uom')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="volume_m3">Volume M3</label>
              <input type="number" step="0.01" class="form-control @error('volume_m3') is-invalid @enderror"
                id="volume_m3" name="volume_m3" value="{{ old('volume_m3', $item->volume_m3) }}">
              @error('volume_m3')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 5: SPQ Ctn & SPQ Item -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="spq_ctn">SPQ Ctn</label>
              <input type="number" class="form-control @error('spq_ctn') is-invalid @enderror" id="spq_ctn"
                name="spq_ctn" value="{{ old('spq_ctn', $item->spq_ctn) }}">
              @error('spq_ctn')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="spq_item">SPQ Item</label>
              <input type="number" class="form-control @error('spq_item') is-invalid @enderror" id="spq_item"
                name="spq_item" value="{{ old('spq_item', $item->spq_item) }}">
              @error('spq_item')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 6: SPQ Weight/Car & M3/Pallet -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="spq_weight">SPQ Weight/Car</label>
              <input type="number" step="0.01" class="form-control @error('spq_weight') is-invalid @enderror"
                id="spq_weight" name="spq_weight" value="{{ old('spq_weight', $item->spq_weight) }}">
              @error('spq_weight')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="m3_pallet">M3/Pallet</label>
              <input type="number" step="0.01" class="form-control @error('m3_pallet') is-invalid @enderror"
                id="m3_pallet" name="m3_pallet" value="{{ old('m3_pallet', $item->m3_pallet) }}">
              @error('m3_pallet')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 7: SPQ Pallet & Item Category -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="spq_pallet">SPQ Pallet</label>
              <input type="number" class="form-control @error('spq_pallet') is-invalid @enderror" id="spq_pallet"
                name="spq_pallet" value="{{ old('spq_pallet', $item->spq_pallet) }}">
              @error('spq_pallet')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="item_cat_id">Item Category <span class="text-danger">*</span></label>
              <select class="form-control @error('item_cat_id') is-invalid @enderror" id="item_cat_id"
                name="item_cat_id" required>
                <option value="">-- Select Category --</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->item_cat_id }}" {{ old('item_cat_id', $item->item_cat_id) == $cat->item_cat_id ?
                  'selected' : '' }}>
                  {{ $cat->item_cat_name }}
                </option>
                @endforeach
              </select>
              @error('item_cat_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 8: Barcode & Remarks -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="barcode">Barcode</label>
              <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode"
                value="{{ old('barcode', $item->barcode) }}">
              @error('barcode')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="remarks">Remarks</label>
              <textarea class="form-control @error('remarks') is-invalid @enderror" id="remarks" name="remarks"
                rows="2">{{ old('remarks', $item->remarks) }}</textarea>
              @error('remarks')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row 9: Standard Price & COA Account -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="standard_price">Standard Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">Rp</span>
                </div>
                <input type="number" step="0.01" class="form-control @error('standard_price') is-invalid @enderror"
                  id="standard_price" name="standard_price" value="{{ old('standard_price', $item->standard_price) }}"
                  required>
                @error('standard_price')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="coa_id">COA Account</label>
              <input type="text" class="form-control @error('coa_id') is-invalid @enderror" id="coa_id" name="coa_id"
                value="{{ old('coa_id', $item->coa_id) }}">
              @error('coa_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">Chart of Account ID</small>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <button type="submit" class="btn btn-warning">
          <i class="fas fa-save mr-1"></i> Update Item
        </button>
        <a href="{{ route('itemmaster.index') }}" class="btn btn-secondary">
          <i class="fas fa-times mr-1"></i> Cancel
        </a>
      </div>
    </form>
  </div>
</div>
@endsection