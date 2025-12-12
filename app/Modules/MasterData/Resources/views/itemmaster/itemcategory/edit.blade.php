@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Category: {{ $category->item_cat_name }}</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('itemcategory.update', $category->item_cat_id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Info Data -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Info:</strong>
                                Created by <strong>{{ $category->input_by }}</strong>
                                on
                                <strong>{{ \Carbon\Carbon::parse($category->input_date)->translatedFormat('d F Y H:i') }}</strong>

                                @if ($category->edit_by)
                                    <br>Last updated by <strong>{{ $category->edit_by }}</strong>
                                    on
                                    <strong>{{ \Carbon\Carbon::parse($category->edit_date)->translatedFormat('d F Y H:i') }}</strong>
                                @endif
                            </div>

                            <div class="form-group">
                                <label for="item_cat_name">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('item_cat_name') is-invalid @enderror"
                                    id="item_cat_name" name="item_cat_name"
                                    value="{{ old('item_cat_name', $category->item_cat_name) }}" required>
                                @error('item_cat_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="item_cat_type">Category Type <span class="text-danger">*</span></label>
                                <select class="form-control @error('item_cat_type') is-invalid @enderror" id="item_cat_type"
                                    name="item_cat_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="Inventory"
                                        {{ old('item_cat_type', $category->transaction_status == 1 ? 'Inventory' : '') == 'Inventory' ? 'selected' : '' }}>
                                        Inventory</option>
                                    <option value="Non-Inventory"
                                        {{ old('item_cat_type', $category->transaction_status == 0 ? 'Non-Inventory' : '') == 'Non-Inventory'
                                            ? 'selected'
                                            : '' }}>
                                        Non-Inventory
                                    </option>
                                </select>
                                @error('item_cat_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="item_cat_desc">Description</label>
                                <textarea class="form-control @error('item_cat_desc') is-invalid @enderror" id="item_cat_desc" name="item_cat_desc"
                                    rows="3">{{ old('item_cat_desc', $category->item_cat_desc) }}</textarea>
                                @error('item_cat_desc')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update
                                </button>
                                <a href="{{ route('itemcategory.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
