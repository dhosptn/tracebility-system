@extends('layouts.app')

@section('title', 'BOM Detail')

@section('content')
<section class="content">
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-12 text-right">
        <a href="{{ route('production.bom.edit', $bom->bom_id) }}" class="btn btn-info">
          <i class="fas fa-edit"></i> Edit
        </a>
        <a href="{{ route('production.bom.index') }}" class="btn btn-danger">
          <i class="fas fa-times"></i> Back
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-light">
        <h3 class="card-title">BOM Detail</h3>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <table class="table table-borderless table-sm text-sm">
              <tr>
                <th width="30%">BOM Name</th>
                <td>: {{ $bom->bom_name }}</td>
              </tr>
              <tr>
                <th>Part Number</th>
                <td>: {{ $bom->part_no }}</td>
              </tr>
              <tr>
                <th>Part Name</th>
                <td>: {{ $bom->part_name }}</td>
              </tr>
              <tr>
                <th>Part Description</th>
                <td>: {{ $bom->part_desc }}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-borderless table-sm">
              <tr>
                <th width="30%">BOM Remarks</th>
                <td>: {{ $bom->bom_rmk }}</td>
              </tr>
              <tr>
                <th>Active Date</th>
                <td>: {{ \Carbon\Carbon::parse($bom->bom_active_date)->format('d F Y') }}</td>
              </tr>
              <tr>
                <th>BOM Status</th>
                <td>: {{ $bom->bom_status == 1 ? 'Active' : 'Inactive' }}</td>
              </tr>
              <tr>
                <th>Created By</th>
                <td>: {{ $bom->input_by }} | {{ \Carbon\Carbon::parse($bom->input_date)->format('d F Y') }}</td>
              </tr>
            </table>
          </div>
        </div>

        <h4 class="mt-4">Daftar Bahan</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-striped text-sm">
            <thead>
              <tr>
                <th>No</th>
                <th>Item Number</th>
                <th>Item Name</th>
                <th>Description</th>
                <th>UOM</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Harga per Unit (Rp)</th>
                <th class="text-right">Total (Rp)</th>
              </tr>
            </thead>
            <tbody>
              @php $grandTotal = 0; @endphp
              @foreach($bom->details as $index => $detail)
              @php $grandTotal += $detail->bom_total_cost; @endphp
              <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $detail->part_no }}</td>
                <td>{{ $detail->part_name }}</td>
                <td>{{ $detail->part_desc }}</td>
                <td>{{ $detail->uom }}</td>
                <td class="text-right">{{ number_format($detail->bom_dtl_qty, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->bom_unit_cost, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->bom_total_cost, 0, ',', '.') }}</td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <td colspan="7" class="text-right font-weight-bold">Total Cost:</td>
                <td class="text-right font-weight-bold">{{ number_format($grandTotal, 0, ',', '.') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection