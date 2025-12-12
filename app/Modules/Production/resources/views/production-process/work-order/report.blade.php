@extends('layouts.app')

@section('title', 'Control Lot Table - ' . $wo->wo_no)

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h6 class="m-0">Work Order Report</h6>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('production.work-order.index') }}">Work Order</a></li>
                        <li class="breadcrumb-item active">Report</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header no-print">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt mr-1"></i>
                        Control Lot Table
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('production.work-order.show', $wo->wo_id) }}" class="btn btn-tool"
                            title="Back to Detail">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button onclick="window.print()" class="btn btn-tool" title="Print">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">

                    <div class="text-center mb-4">
                        <h3 class="font-weight-bold">CONTROL LOT TABLE</h3>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td style="width: 150px; font-weight: bold;">PART No</td>
                                    <td>
                                        <div class="border rounded p-2 bg-light">{{ $wo->part_no }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold;">PART Name</td>
                                    <td>
                                        <div class="border rounded p-2 bg-light">{{ $wo->part_name }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold;">Nama Pembuat</td>
                                    <td>
                                        <div class="border rounded p-2 bg-light">-</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td style="width: 150px; font-weight: bold;">Charge No</td>
                                    <td>
                                        <div class="border rounded p-2 bg-light">-</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold;">WO No.</td>
                                    <td>
                                        <div class="border rounded p-2 bg-light">{{ $wo->wo_no }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold;">WO Qty</td>
                                    <td>
                                        <div class="border rounded p-2 bg-light">{{ $wo->wo_qty }} PCS</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="text-center font-weight-bold mb-3" style="font-size: 18px;">{{ $wo->part_name }}</div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-sm report-table">
                            <thead class="bg-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" width="3%">No</th>
                                    <th rowspan="2" class="align-middle text-center" width="15%">Nama Proses</th>
                                    <th rowspan="2" class="align-middle text-center" width="15%">POS Produksi</th>
                                    <th rowspan="2" class="align-middle text-center" width="10%">Tanggal Produksi</th>
                                    <th colspan="2" class="text-center">Produksi</th>
                                    <th rowspan="2" class="align-middle text-center" width="5%">Shift</th>
                                    <th colspan="2" class="text-center">NG Part</th>
                                    <th colspan="2" class="text-center">OK Part</th>
                                    <th rowspan="2" class="align-middle text-center" width="8%">Operator</th>
                                    <th colspan="3" class="text-center">Jika Produksi Terbagi</th>
                                    <th rowspan="2" class="align-middle text-center">Remarks</th>
                                </tr>
                                <tr>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Kumulatif</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Kumulatif</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Kumulatif</th>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Ambil</th>
                                    <th class="text-center">Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($wo->routing && $wo->routing->details)
                                    @foreach ($wo->routing->details as $index => $detail)
                                        <tr>
                                            <td rowspan="2" class="align-middle text-center">{{ $index + 1 }}</td>
                                            <td rowspan="2" class="align-middle pl-2">{{ $detail->process_name }}</td>
                                            <td rowspan="2" class="align-middle pl-2">{{ $detail->process_name }}</td>
                                            <td></td> {{-- Tanggal Produksi --}}
                                            <td></td> {{-- Produksi Total --}}
                                            <td></td> {{-- Produksi Kumulatif --}}
                                            <td></td> {{-- Shift --}}
                                            <td></td> {{-- NG Qty --}}
                                            <td></td> {{-- NG Kumulatif --}}
                                            <td></td> {{-- OK Qty --}}
                                            <td></td> {{-- OK Kumulatif --}}
                                            <td></td> {{-- Operator --}}
                                            <td></td> {{-- Tanggal --}}
                                            <td></td> {{-- Ambil --}}
                                            <td></td> {{-- Sisa --}}
                                            <td rowspan="2"></td> {{-- Remarks --}}
                                        </tr>
                                        <tr>
                                            {{-- Empty row for second shift or split --}}
                                            <td style="height: 25px;"></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="17" class="text-center text-muted">No process data available.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .content-header {
                display: none !important;
            }

            .main-footer {
                display: none !important;
            }
        }

        .report-table th,
        .report-table td {
            vertical-align: middle !important;
        }
    </style>
@endsection
