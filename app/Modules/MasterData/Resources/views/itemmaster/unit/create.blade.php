@extends('layouts.app')

@section('title', 'Create New UOM')

@section('content')


<div class="card card-primary card-outline">
  <div class="card-header">
    <h3 class="card-title">New Unit Form</h3>
  </div>
  <form action="{{ route('master-data.unit.store') }}" method="POST">
    @csrf
    <div class="card-body">
      <div class="form-group">
        <label for="uom_code">Unit Code <span class="text-danger">*</span></label>
        <input type="text" name="uom_code" class="form-control @error('uom_code') is-invalid @enderror"
          value="{{ old('uom_code') }}" placeholder="Enter unit code (e.g. KG, PCS)">
        @error('uom_code')
        <span class="invalid-feedback">{{ $message }}</span>
        @enderror
      </div>
      <div class="form-group">
        <label for="uom_desc">Description <span class="text-danger">*</span></label>
        <input type="text" name="uom_desc" class="form-control @error('uom_desc') is-invalid @enderror"
          value="{{ old('uom_desc') }}" placeholder="Enter description (e.g. Kilogram, Pieces)">
        @error('uom_desc')
        <span class="invalid-feedback">{{ $message }}</span>
        @enderror
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Unit</button>
      <a href="{{ route('master-data.unit.index') }}" class="btn btn-secondary"><i class="fas fa-times mr-1"></i>
        Cancel</a>
    </div>
  </form>
</div>
@endsection