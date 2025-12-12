@extends('layouts.app')

@section('title', 'Work Order Detail')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <a href="{{ route('production.work-order.index') }}" class="btn btn-secondary mr-2">
                        <i class="fas fa-arrow-left"></i> Back to Work Order List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">WO No <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $wo->wo_no }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">WO Date <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    value="{{ $wo->wo_date ? $wo->wo_date->format('d/m/Y') : '-' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">Production Date <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    value="{{ $wo->prod_date ? $wo->prod_date->format('d/m/Y') : '-' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">WO Qty <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $wo->wo_qty }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">Part Number <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $wo->part_no }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">Nama Produk</label>
                                <input type="text" class="form-control" value="{{ $wo->part_name }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">UOM (Unit of Measure)</label>
                                <input type="text" class="form-control" value="{{ $wo->uom_id }}" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">WO Remarks</label>
                                <input type="text" class="form-control" value="{{ $wo->wo_rmk ?? '-' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-muted font-weight-normal">Status <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="{{ $wo->wo_status }}" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5 class="mb-3">Process List Preview</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" width="5%">No</th>
                                    <th rowspan="2" class="align-middle" width="20%">Process Name</th>
                                    <th rowspan="2" class="align-middle" width="20%">Description</th>
                                    <th rowspan="2" class="align-middle text-center" width="10%">Lot Qty</th>
                                    <th rowspan="2" class="align-middle text-center" width="10%">Remain Qty</th>
                                    <th rowspan="2" class="align-middle text-center" width="10%">Start Date</th>
                                    <th rowspan="2" class="align-middle text-center" width="10%">Last Prod. Dt.</th>
                                    <th colspan="4" class="text-center">Production Info</th>
                                </tr>
                                <tr>
                                    <th class="text-center">Shift</th>
                                    <th class="text-center">OK Qty</th>
                                    <th class="text-center">NG Qty</th>
                                    <th class="text-center">Total Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($wo->routing && $wo->routing->details)
                                    @foreach ($wo->routing->details as $index => $detail)
                                        <tr>
                                            <td rowspan="2" class="align-middle text-center">{{ $index + 1 }}</td>
                                            <td rowspan="2" class="align-middle">{{ $detail->process_name }}</td>
                                            <td rowspan="2" class="align-middle">{{ $detail->process_desc }}</td>
                                            <td rowspan="2" class="align-middle text-center">{{ $wo->wo_qty }}</td>
                                            <td rowspan="2" class="align-middle text-center">0</td>
                                            {{-- Placeholder for Remain Qty --}}
                                            <td rowspan="2" class="align-middle text-center"></td>
                                            {{-- Placeholder for Start Date --}}
                                            <td rowspan="2" class="align-middle text-center"></td>
                                            {{-- Placeholder for Last Prod Dt --}}

                                            {{-- Shift 1 Row --}}
                                            <td class="text-center">1</td>
                                            <td class="text-center">0</td>
                                            <td class="text-center">0</td>
                                            <td class="text-center"></td>
                                        </tr>
                                        <tr>
                                            {{-- Shift 2 Row --}}
                                            <td class="text-center">2</td>
                                            <td class="text-center">0</td>
                                            <td class="text-center">0</td>
                                            <td class="text-center"></td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">No process data available.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
