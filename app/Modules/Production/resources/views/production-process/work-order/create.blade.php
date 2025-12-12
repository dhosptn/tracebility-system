@extends('layouts.app')

@section('title', 'Create Work Order')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Form Create Work Order</h3>
                    <div class="card-tools">
                        <a href="{{ route('production.work-order.index') }}" class="btn btn-tool" title="Back to List">
                            <i class="fas fa-times"></i> Back to Work Order List
                        </a>
                    </div>
                </div>

                <form action="{{ route('production.work-order.store') }}" method="POST" id="wo-form">
                    @csrf
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="wo_no">WO No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('wo_no') is-invalid @enderror"
                                        id="wo_no" name="wo_no" value="{{ old('wo_no', $autoWoNo) }}" readonly>
                                    @error('wo_no')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="wo_date">WO Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('wo_date') is-invalid @enderror"
                                        id="wo_date" name="wo_date" value="{{ old('wo_date', date('Y-m-d')) }}" required>
                                    @error('wo_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="prod_date">Production Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('prod_date') is-invalid @enderror"
                                        id="prod_date" name="prod_date" value="{{ old('prod_date', date('Y-m-d')) }}"
                                        required>
                                    @error('prod_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="wo_qty">WO Qty <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('wo_qty') is-invalid @enderror" id="wo_qty"
                                        name="wo_qty" placeholder="WO Qty" value="{{ old('wo_qty') }}" required>
                                    @error('wo_qty')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="part_no">Part Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('part_no') is-invalid @enderror"
                                            id="part_no" name="part_no" placeholder="Otomatis terisi"
                                            value="{{ old('part_no') }}" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-default" type="button" id="btn-select-part">
                                                <i class="fas fa-search"></i> Pilih Produk
                                            </button>
                                        </div>
                                    </div>
                                    @error('part_no')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="part_name">Nama Produk</label>
                                    <input type="text" class="form-control" id="part_name" name="part_name"
                                        placeholder="Masukkan nama produk" value="{{ old('part_name') }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="uom">UOM (Unit of Measure)</label>
                                    <input type="text" class="form-control" id="uom" name="uom"
                                        placeholder="Otomatis terisi" value="{{ old('uom') }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lot_id">Lot Number</label>
                                    <select class="form-control" id="lot_id" name="lot_id">
                                        <option value="">Select Lot</option>
                                        @foreach ($lots as $lot)
                                            <option value="{{ $lot->lot_id }}"
                                                {{ old('lot_id') == $lot->lot_id ? 'selected' : '' }}>{{ $lot->lot_no }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('lot_id')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="wo_rmk">WO Remarks</label>
                                    <input type="text" class="form-control" id="wo_rmk" name="wo_rmk"
                                        placeholder="Remarks" value="{{ old('wo_rmk') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="wo_status">Status <span class="text-danger">*</span></label>
                                    <select class="form-control" id="wo_status" name="wo_status">
                                        <option value="Release">Release</option>
                                        <option value="On Process">On Process</option>
                                        <option value="Built">Built</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="mb-3">Process List Preview</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-sm" id="process-preview-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Process Name</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody id="process-preview-body">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No process data available.
                                            Select a product first.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="card-footer text-right">
                        <button type="button" class="btn btn-secondary mr-2" id="btn-reset">
                            <i class="fas fa-undo"></i> RESET
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> SUBMIT WO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Modal untuk pilih produk -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-hover table-striped text-sm" id="product-table"
                        style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="25%">Product Name</th>
                                <th width="10%">UOM</th>
                                <th width="10%">Model</th>
                                <th width="15%">Category</th>
                                <th width="25%">Description</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->uom->uom_code ?? '-' }}</td>
                                    <td>{{ $item->model ?? '-' }}</td>
                                    <td>{{ $item->category->item_cat_name ?? '-' }}</td>
                                    <td>{{ $item->item_description ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-primary select-product"
                                            data-part-no="{{ $item->item_number }}"
                                            data-part-name="{{ $item->item_name }}"
                                            data-part-desc="{{ $item->uom->uom_code ?? '' }}">
                                            <i class="fas fa-check"></i> Select
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
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
            // Check if there is old data for part_no (e.g. after validation error)
            var oldPartNo = "{{ old('part_no') }}";
            if (oldPartNo) {
                fetchPartDetails(oldPartNo);
            }

            // Product modal
            $('#btn-select-part').click(function() {
                $('#productModal').modal('show');
            });

            // Initialize DataTable for product modal
            $('#product-table').DataTable({
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                }
            });

            // Select product
            $(document).on('click', '.select-product', function() {
                let partNo = $(this).data('part-no');
                let partName = $(this).data('part-name');
                let uom = $(this).data('part-desc'); // Using data-part-desc for UOM as per previous code

                $('#part_no').val(partNo);
                $('#part_name').val(partName);
                $('#uom').val(uom);
                $('#productModal').modal('hide');

                // Fetch Part Details (Process List)
                fetchPartDetails(partNo);
            });

            function fetchPartDetails(partNo) {
                $.ajax({
                    url: "{{ route('production.work-order.part-details') }}",
                    type: "GET",
                    data: {
                        part_no: partNo
                    },
                    success: function(response) {
                        let rows = '';
                        if (response.processes && response.processes.length > 0) {
                            response.processes.forEach((process, index) => {
                                rows += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${process.process_name}</td>
                                    <td>${process.process_desc || '-'}</td>
                                </tr>
                            `;
                            });
                        } else {
                            rows =
                                '<tr><td colspan="3" class="text-center text-muted">No process data found for this product. Please create Routing (Settings Process) for this item.</td></tr>';
                        }
                        $('#process-preview-body').html(rows);
                    },
                    error: function() {
                        alert('Failed to fetch process details.');
                    }
                });
            }

            // Reset form
            $('#btn-reset').click(function() {
                if (confirm('Are you sure you want to reset the form?')) {
                    $('#wo-form')[0].reset();
                    // Reset select2 if used, or other custom fields
                    $('#process-preview-body').html(
                        '<tr><td colspan="3" class="text-center text-muted">No process data available. Select a product first.</td></tr>'
                    );
                    // Restore Auto WO No
                    $('#wo_no').val('{{ $autoWoNo }}');
                    // Restore Dates
                    $('#wo_date').val('{{ date('Y-m-d') }}');
                    $('#prod_date').val('{{ date('Y-m-d') }}');
                }
            });
        });
    </script>
@endpush
