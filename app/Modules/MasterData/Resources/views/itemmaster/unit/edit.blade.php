@extends('layouts.app')

@section('title', 'Edit UOM')

@section('content')
    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title">Edit Unit: {{ $unit->uom_code }}</h3>
        </div>
        <form action="{{ route('master-data.unit.update', $unit->uom_id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="uom_code">Unit Code <span class="text-danger">*</span></label>
                    <input type="text" name="uom_code" class="form-control @error('uom_code') is-invalid @enderror"
                        value="{{ old('uom_code', $unit->uom_code) }}" placeholder="Enter unit code">
                    @error('uom_code')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="uom_desc">Description <span class="text-danger">*</span></label>
                    <input type="text" name="uom_desc" class="form-control @error('uom_desc') is-invalid @enderror"
                        value="{{ old('uom_desc', $unit->uom_desc) }}" placeholder="Enter description">
                    @error('uom_desc')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Unit</button>
                <a href="{{ route('master-data.unit.index') }}" class="btn btn-secondary"><i class="fas fa-times mr-1"></i>
                    Cancel</a>
            </div>
        </form>
    </div>
@endsection
