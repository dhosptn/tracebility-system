@extends('layouts.app')

@section('title', 'Create BOM')

@section('content')
<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title">Form Input BOM</h3>
        <div class="card-tools">
          <a href="{{ route('bom.index') }}" class="btn btn-tool" title="Back to List">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </div>

      <form action="{{ route('bom.store') }}" method="POST">
        @csrf
        <div class="card-body">
          {{-- BOM HEADER --}}
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>BOM Name <span class="text-danger">*</span></label>
                <input type="text" name="bom_name" class="form-control" value="{{ $autoBomCode ?? '' }}"
                  placeholder="Masukkan Nama BOM" required>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Part Number</label>
                <div class="input-group">
                  <input type="text" id="part_no" name="part_no" class="form-control" readonly
                    placeholder="Otomatis terisi">
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
                  placeholder="Masukkan nama produk">
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
                  placeholder="Otomatis terisi"></textarea>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>BOM Remarks</label>
                <input type="text" name="bom_rmk" class="form-control" placeholder="Remarks">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Active Date</label>
                <input type="date" name="bom_active_date" class="form-control" value="{{ date('Y-m-d') }}">
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <label>Status</label>
                <select name="bom_status" class="form-control">
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
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
                <tr>
                  <td><input name="detail[0][part_no]" class="form-control form-control-sm detail-part-no" readonly>
                  </td>
                  <td>
                    <div class="input-group input-group-sm">
                      <input name="detail[0][part_name]" class="form-control form-control-sm detail-part-name"
                        placeholder="Klik untuk pilih" readonly style="cursor: pointer; background-color: #fff;"
                        onclick="openMaterialModal(this)">
                      <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                      </div>
                    </div>
                  </td>
                  <td><input name="detail[0][part_desc]" class="form-control form-control-sm detail-part-desc" readonly>
                  </td>
                  <td><input name="detail[0][uom]" class="form-control form-control-sm detail-uom" readonly></td>
                  <td><input type="number" step="0.01" name="detail[0][qty]" class="form-control form-control-sm"></td>
                  <td><input type="number" step="0.01" name="detail[0][unit_cost]" class="form-control form-control-sm">
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer text-right">
          <a href="{{ route('bom.index') }}" class="btn btn-default mr-2">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save"></i> Simpan BOM
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
  let currentRowIndex = null; // To track which row triggered the modal

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
                    // Find the row inputs based on the index
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
            let rowIndex = 1;

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

        // Function to open Material Modal and set current row index
        function openMaterialModal(element, index = 0) {
            // If index is passed directly (for dynamic rows), use it. 
            // For the first static row, we need to handle it carefully or just pass 0.

            // Better approach: find the index from the name attribute
            let nameAttr = $(element).attr('name'); // e.g., detail[0][part_name]
            let match = nameAttr.match(/\[(\d+)\]/);
            if (match) {
                currentRowIndex = match[1];
            }

            $('#materialModal').modal('show');
        }
</script>
@endpush