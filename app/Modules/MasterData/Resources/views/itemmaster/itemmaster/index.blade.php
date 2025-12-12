@extends('layouts.app')

@section('title', 'Item Master')

@section('content')
<div class="container-fluid">

  <div class="card">
    <div class="card-header">
      <div class="float-left">
        <a href="{{ route('itemmaster.create') }}?type=inventory" class="btn btn-primary btn-sm mr-1">
          <i class="fas fa-plus mr-1"></i> Add Inventory Item
        </a>
        <a href="{{ route('itemmaster.create') }}?type=non-inventory" class="btn btn-info btn-sm">
          <i class="fas fa-plus mr-1"></i> Add Non-Inventory Item
        </a>
      </div>

      <div class="card-tools float-right">
        <button type="button" class="btn btn-success btn-sm" id="btn-export">
          <i class="fas fa-file-excel mr-1"></i> Export to Excel
        </button>
      </div>
    </div>

    <div class="card-body">
      @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ session('success') }}
      </div>
      @endif

      @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ session('error') }}
      </div>
      @endif

      <!-- Filters -->
      <div class="row mb-3">
        <div class="col-md-3">
          <label>Stock Type</label>
          <select class="form-control form-control-sm" id="filter-stock-type">
            <option value="">All</option>
            <option value="inventory">Inventory Item</option>
            <option value="non-inventory">Non-Inventory Item</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>Category</label>
          <select class="form-control form-control-sm" id="filter-category">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->item_cat_id }}">{{ $cat->item_cat_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label>&nbsp;</label><br>
          <button type="button" class="btn btn-primary btn-sm" id="btn-filter">
            <i class="fas fa-filter"></i> Apply Filter
          </button>
          <button type="button" class="btn btn-secondary btn-sm" id="btn-reset">
            <i class="fas fa-redo"></i> Reset
          </button>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped text-sm" id="items-table">
          <thead>
            <tr>
              <th width="5%">No</th>
              <th>SKU / Part No</th>
              <th>Part Name / Desc</th>
              <th>Model</th>
              <th>Stock Type</th>
              <th>UOM</th>
              <th>Category</th>
              <th>Standard Price</th>
              <th width="12%">Action</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  $(document).ready(function() {
    // Initialize DataTable
    var table = $('#items-table').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "{{ route('itemmaster.data') }}",
        data: function(d) {
          d.stock_type = $('#filter-stock-type').val();
          d.category_id = $('#filter-category').val();
        }
      },
      columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'item_number', name: 'item_number' },
        { 
          data: 'item_name', 
          name: 'item_name',
          render: function(data, type, row) {
            var html = data;
            if (row.item_description) {
              html += '<br><small class="text-muted">' + row.item_description + '</small>';
            }
            return html;
          }
        },
        { data: 'model', name: 'model', defaultContent: '-' },
        { 
          data: 'stock_type', 
          name: 'stock_type',
          render: function(data) {
            if (data === 'inventory') {
              return '<span class="badge badge-success">Inventory</span>';
            } else if (data === 'non-inventory') {
              return '<span class="badge badge-info">Non-Inventory</span>';
            }
            return data;
          }
        },
        { data: 'uom_code', name: 'uom.uom_code' },
        { data: 'category_name', name: 'category.item_cat_name' },
        { 
          data: 'standard_price', 
          name: 'standard_price',
          render: function(data) {
            return 'Rp ' + parseFloat(data).toLocaleString('id-ID');
          }
        },
        { 
          data: 'action', 
          name: 'action', 
          orderable: false, 
          searchable: false,
          render: function(data, type, row) {
            // Pastikan data dari server sudah berisi URL edit
            if (data) {
              return data;
            }
            
            // Alternatif jika data tidak tersedia, buat manual
            var editUrl = "{{ route('itemmaster.edit', ':id') }}".replace(':id', row.id);
            var deleteUrl = "{{ url('master-data/item-master') }}/" + row.id;
            
            return `
              <div class="btn-group" role="group">
                <a href="${editUrl}" class="btn btn-sm btn-warning" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-sm btn-danger btn-delete ml-1" 
                        data-id="${row.id}" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            `;
          }
        }
      ],
      order: [[1, 'asc']],
      pageLength: 10,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
    });

    // Filter button
    $('#btn-filter').on('click', function() {
      table.ajax.reload();
    });

    // Reset button
    $('#btn-reset').on('click', function() {
      $('#filter-stock-type').val('');
      $('#filter-category').val('');
      table.ajax.reload();
    });

    // Delete button
    $(document).on('click', '.btn-delete', function() {
      var itemId = $(this).data('id');
      var deleteUrl = "{{ url('master-data/item-master') }}/" + itemId;
      
      if (confirm('Are you sure you want to delete this item?')) {
        $.ajax({
          url: deleteUrl,
          type: 'DELETE',
          data: {
            _token: '{{ csrf_token() }}'
          },
          success: function(response) {
            if (response.success) {
              table.ajax.reload();
              alert(response.message);
            }
          },
          error: function(xhr) {
            alert('Error deleting item');
          }
        });
      }
    });

    // Export to Excel
    $('#btn-export').on('click', function() {
      var stockType = $('#filter-stock-type').val();
      var categoryId = $('#filter-category').val();
      
      var url = "{{ route('itemmaster.export') }}?";
      if (stockType) url += 'stock_type=' + stockType + '&';
      if (categoryId) url += 'category_id=' + categoryId;
      
      window.location.href = url;
    });
  });
</script>
@endpush