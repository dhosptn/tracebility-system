@extends('layouts.master')
@section('title',$title)
@section('content')
<form method="POST" action="{{ $row ? route($route.'.save',$row->id) : route($route.'.save') }}">
  @csrf
  @foreach($fields as $field)
  <div class="form-group">
    <label>{{ $field['label'] }}</label>
    <input type="text" name="{{ $field['name'] }}" class="form-control"
      value="{{ old($field['name'], $row->{$field['name']} ?? '') }}">
  </div>
  @endforeach
  <button class="btn btn-primary">{{ $row ? 'Update' : 'Save' }}</button>
  <a href="{{ route($route.'.index') }}" class="btn btn-secondary">Back</a>
</form>
@endsection