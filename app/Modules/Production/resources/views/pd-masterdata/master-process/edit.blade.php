@extends('layouts.app')

@section('title', 'Edit Master Process')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Form Edit Process</h3>
                    <div class="card-tools">
                        <a href="{{ route('production.master-process.index') }}" class="btn btn-tool" title="Back to List">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>

                <form action="{{ route('production.master-process.update', $process->proces_id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="process_name">Process Name <span class="text-danger">*</span></label>
                                    <input type="text" name="process_name" id="process_name"
                                        class="form-control @error('process_name') is-invalid @enderror"
                                        value="{{ old('process_name', $process->process_name) }}" required
                                        placeholder="Enter process name">
                                    @error('process_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="process_desc">Process Description</label>
                                    <textarea name="process_desc" id="process_desc" class="form-control @error('process_desc') is-invalid @enderror"
                                        rows="4" placeholder="Enter process description">{{ old('process_desc', $process->process_desc) }}</textarea>
                                    @error('process_desc')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('production.master-process.index') }}" class="btn btn-default mr-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Process
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
