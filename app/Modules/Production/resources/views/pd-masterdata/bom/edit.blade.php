@extends('layouts.app')

@section('title', 'Edit BOM')

@section('content')
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Form Edit BOM</h3>
        <div class="card-tools">
          <a href="{{ route('bom.index') }}" class="btn btn-tool" title="Back to List">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </div>

      <form action="{{ route('bom.update', $bom->bom_id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
          {{-- BOM HEADER --}}
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>BOM Name <span class="text-danger">*</span></label>
                <input type="text" name="bom_name" class="form-control" value="{{ old('bom_name', $bom->bom_name) }}"
                  placeholder="Masukkan Nama BOM" required>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Part Number</label>
                <div class="input-group">
                  <input type="text" id="part_no" name="part_no" class="form-control"
                    value="{{ old('part_no', $bom->part_no) }}" readonly placeholder="Otomatis terisi">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#productModal">
                      <i class="fas fa-search"></i> Select
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" id="part_name" name="part_name" class="form-control"
                  value="{{ old('part_name', $bom->part_name) }}" placeholder="Masukkan nama produk">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>UOM</label>
                <input type="text" id="uom" name="uom" class="form-control" readonly placeholder="Otomatis terisi">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="form-group">
                <label>Deskripsi</label>
                <textarea id="part_desc" name="part_desc" class="form-control" rows="2"
                  placeholder="Otomatis terisi">{{ old('part_desc', $bom->part_desc) }}</textarea>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>BOM Remarks</label>
                <input type="text" name="bom_rmk" class="form-control" value="{{ old('bom_rmk', $bom->bom_rmk) }}"
                  placeholder="Remarks">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Active Date</label>
                <input type="date" name="bom_active_date" class="form-control"
                  value="{{ old('bom_active_date', $bom->bom_active_date) }}">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Status</label>
                <select name="bom_status" class="form-control">
                  <option value="1" {{ $bom->bom_status == 1 ? 'selected' : '' }}>Active
                  </option>
                  <option value="0" {{ $bom->bom_status == 0 ? 'selected' : '' }}>Inactive
                  </option>
                </select>
              </div>
            </div>
          </div>

          {{-- ITEM DETAIL --}}
          <hr>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-muted">BOM Item Detail</h5>
            <button type="button" id="addRow" class="btn btn-success btn-sm">
              <i class="fas fa-plus"></i> Tambah Item
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped text-sm" id="itemTable">
              <thead class="bg-light">
                <tr>
                  <th style="width: 15%">Item Number</th>
                  <th style="width: 20%">Item Name</th>
                  <th style="width: 20%">Description</th>
                  <th style="width: 10%">UOM</th>
                  <th style="width: 10%">Jumlah</th>
                  <th style="width: 15%">Harga per Unit (Rp)</th>
                  <th style="width: 10%" class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="itemBody">
                @foreach ($bom->details as $index => $detail)
                <tr>
                  <td><input name="detail[{{ $index }}][part_no]" class="form-control form-control-sm detail-part-no"
                      value="{{ $detail->part_no }}" readonly></td>
                  <td>
                    <div class="input-group input-group-sm">
                      <input name="detail[{{ $index }}][part_name]"
                        class="form-control form-control-sm detail-part-name" value="{{ $detail->part_name }}"
                        placeholder="Klik untuk pilih" readonly style="cursor: pointer; background-color: #fff;"
                        onclick="openMaterialModal(this)">
                      <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                      </div>
                    </div>
                  </td>
                  <td><input name="detail[{{ $index }}][part_desc]"
                      class="form-control form-control-sm detail-part-desc" value="{{ $detail->part_desc }}" readonly>
                  </td>
                  <td><input name="detail[{{ $index }}][uom]" class="form-control form-control-sm detail-uom"
                      value="{{ $detail->uom }}" readonly></td>
                  <td>
                    <input type="number" step="0.0001" name="detail[{{ $index }}][qty]"
                      class="form-control form-control-sm" value="{{ str_replace(',', '.', $detail->bom_dtl_qty) }}"
                      required>
                  </td>
                  <td>
                    <input type="number" step="0.01" name="detail[{{ $index }}][unit_cost]"
                      class="form-control form-control-sm" value="{{ str_replace(',', '.', $detail->bom_unit_cost) }}"
                      required>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-right">
          <a href="{{ route('bom.index') }}" class="btn btn-default mr-2">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save"></i> Update BOM
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Product Modal (For Main BOM Product) --}}
  <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productModalLabel">Pilih Produk Utama</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped text-sm" id="productTable" style="width:100%">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Item Number</th>
                  <th>Item Name</th>
                  <th>UOM</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Material Modal (For BOM Details) --}}
  <div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="materialModalLabel">Pilih Bahan Baku</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped text-sm" id="materialTable" style="width:100%">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Item Number</th>
                  <th>Item Name</th>
                  <th>UOM</th>
                  <th>Description</th>
                  <th>Category</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  let currentRowIndex = null;

        $(document).ready(function() {
            // --- 1. Product DataTable (Main Product) ---
            var productTable = $('#productTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('itemmaster.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'item_number',
                        name: 'item_number'
                    },
                    {
                        data: 'item_name',
                        name: 'item_name'
                    },
                    {
                        data: 'uom_code',
                        name: 'uom.uom_code'
                    },
                    {
                        data: 'item_description',
                        name: 'item_description',
                        defaultContent: '-'
                    },
                    {
                        data: 'category_name',
                        name: 'category.item_cat_name'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `<button type="button" class="btn btn-primary btn-xs btn-select-product" 
              data-item-number="${row.item_number}"
              data-item-name="${row.item_name}"
              data-item-desc="${row.item_description || ''}"
              data-uom="${row.uom_code || ''}">
              <i class="fas fa-check"></i> Pilih
            </button>`;
                        }
                    }
                ]
            });

            $('#productTable').on('click', '.btn-select-product', function() {
                var itemNumber = $(this).data('item-number');
                var itemName = $(this).data('item-name');
                var itemDesc = $(this).data('item-desc');
                var uom = $(this).data('uom');

                $('#part_no').val(itemNumber);
                $('#part_name').val(itemName);
                $('#part_desc').val(itemDesc);
                $('#uom').val(uom);

                $('#productModal').modal('hide');
            });

            // --- 2. Material DataTable (BOM Details) ---
            var materialTable = $('#materialTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('itemmaster.data') }}?exclude_category_name=FG",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'item_number',
                        name: 'item_number'
                    },
                    {
                        data: 'item_name',
                        name: 'item_name'
                    },
                    {
                        data: 'uom_code',
                        name: 'uom.uom_code'
                    },
                    {
                        data: 'item_description',
                        name: 'item_description',
                        defaultContent: '-'
                    },
                    {
                        data: 'category_name',
                        name: 'category.item_cat_name'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `<button type="button" class="btn btn-success btn-xs btn-select-material" 
              data-item-number="${row.item_number}"
              data-item-name="${row.item_name}"
              data-item-desc="${row.item_description || ''}"
              data-uom="${row.uom_code || ''}"
              data-cost="${row.standard_price || 0}">
              <i class="fas fa-check"></i> Pilih
            </button>`;
                        }
                    }
                ]
            });

            $('#materialTable').on('click', '.btn-select-material', function() {
                var itemNumber = $(this).data('item-number');
                var itemName = $(this).data('item-name');
                var itemDesc = $(this).data('item-desc');
                var uom = $(this).data('uom');
                var cost = $(this).data('cost');

                if (currentRowIndex !== null) {
                    let row = $(`input[name="detail[${currentRowIndex}][part_name]"]`).closest('tr');

                    row.find('.detail-part-no').val(itemNumber);
                    row.find('.detail-part-name').val(itemName);
                    row.find('.detail-part-desc').val(itemDesc);
                    row.find('.detail-uom').val(uom);
                    row.find('input[name$="[unit_cost]"]').val(cost);
                }

                $('#materialModal').modal('hide');
            });

            // --- 3. Dynamic Rows ---
            let rowIndex = {{ count($bom->details) }};

            document.getElementById('addRow').addEventListener('click', function() {
                let row =
                    `<tr>
            <td><input name="detail[${rowIndex}][part_no]" class="form-control form-control-sm detail-part-no" readonly></td>
            <td>
                <div class="input-group input-group-sm">
                  <input name="detail[${rowIndex}][part_name]" class="form-control form-control-sm detail-part-name" placeholder="Klik untuk pilih" readonly style="cursor: pointer; background-color: #fff;" onclick="openMaterialModal(this, ${rowIndex})">
                  <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                  </div>
                </div>
            </td>
            <td><input name="detail[${rowIndex}][part_desc]" class="form-control form-control-sm detail-part-desc" readonly></td>
            <td><input name="detail[${rowIndex}][uom]" class="form-control form-control-sm detail-uom" readonly></td>
            <td><input type="number" step="0.01" name="detail[${rowIndex}][qty]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" name="detail[${rowIndex}][unit_cost]" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fas fa-trash"></i></button></td>
        </tr>`;

                document.getElementById('itemBody').insertAdjacentHTML('beforeend', row);

                rowIndex++;
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('removeRow') || e.target.closest('.removeRow')) {
                    e.target.closest('tr').remove();
                }
            });
        });

        function openMaterialModal(element, index = 0) {
            let nameAttr = $(element).attr('name');
            let match = nameAttr.match(/\[(\d+)\]/);
            if (match) {
                currentRowIndex = match[1];
            }

            $('#materialModal').modal('show');
        }
</script>
@endpush