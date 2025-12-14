@extends('layouts.app')

@section('title', 'Transaction Detail')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye mr-1"></i>
                        Transaction Detail
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('production.wo-transaction.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>

                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Transaction No</th>
                                    <td>{{ $transaction->trx_no }}</td>
                                </tr>
                                <tr>
                                    <th>Transaction Date</th>
                                    <td>{{ $transaction->trx_date ? \Carbon\Carbon::parse($transaction->trx_date)->format('d-m-Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>WO No</th>
                                    <td>{{ $transaction->wo_no }}</td>
                                </tr>
                                <tr>
                                    <th>Part Name</th>
                                    <td>{{ $transaction->workOrder ? $transaction->workOrder->part_name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Part No</th>
                                    <td>{{ $transaction->workOrder ? $transaction->workOrder->part_no : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Process</th>
                                    <td>{{ $transaction->process_name }}</td>
                                </tr>
                                <tr>
                                    <th>Cycle Time</th>
                                    <td>{{ $transaction->cycle_time }} seconds</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Supervisor</th>
                                    <td>{{ $transaction->supervisor ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Operator</th>
                                    <td>{{ $transaction->operator ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Machine</th>
                                    <td>{{ $transaction->machine ? $transaction->machine->machine_name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Shift</th>
                                    <td>Shift {{ $transaction->shift_id ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Start Time</th>
                                    <td>{{ $transaction->start_time ? \Carbon\Carbon::parse($transaction->start_time)->format('d-m-Y H:i') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>End Time</th>
                                    <td>{{ $transaction->end_time ? \Carbon\Carbon::parse($transaction->end_time)->format('d-m-Y H:i') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span
                                            class="badge badge-{{ $transaction->status == 'Draft' ? 'secondary' : 'success' }}">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <h5>Production Quantity</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Target Qty</th>
                                        <th>Actual Qty</th>
                                        <th>OK Qty</th>
                                        <th>NG Qty</th>
                                        <th>OEE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">{{ number_format($transaction->target_qty ?? 0) }}</td>
                                        <td class="text-center">{{ number_format($transaction->actual_qty ?? 0) }}</td>
                                        <td class="text-center">{{ number_format($transaction->ok_qty ?? 0) }}</td>
                                        <td class="text-center">{{ number_format($transaction->ng_qty ?? 0) }}</td>
                                        <td class="text-center">
                                            @php
                                                $oee = 0;
                                                if ($transaction->actual_qty > 0) {
                                                    $oee = round(
                                                        ($transaction->ok_qty / $transaction->actual_qty) * 100,
                                                        2,
                                                    );
                                                }
                                            @endphp
                                            {{ $oee }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($transaction->notes)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Notes</h5>
                                <p>{{ $transaction->notes }}</p>
                            </div>
                        </div>
                    @endif

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Created by: {{ $transaction->input_by ?? '-' }}<br>
                                Created at:
                                {{ $transaction->input_time ? \Carbon\Carbon::parse($transaction->input_time)->format('d-m-Y H:i:s') : '-' }}
                            </small>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                @if ($transaction->edit_by)
                                    Last edited by: {{ $transaction->edit_by }}<br>
                                    Last edited at:
                                    {{ $transaction->edit_time ? \Carbon\Carbon::parse($transaction->edit_time)->format('d-m-Y H:i:s') : '-' }}
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
