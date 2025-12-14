@extends('layouts.app')

@section('title', 'Bill of Material')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">BOM List</h3>
                    <div class="card-tools">
                        <a href="{{ route('production.bom.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add BOM
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="bomTable" class="table table-bordered table-striped table-hover text-sm"
                            style="width: 100%">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>BOM Name</th>
                                    <th>Part No</th>
                                    <th>Part Name</th>
                                    <th>Active Date</th>
                                    <th>Status</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(function() {
            var table = $('#bomTable').DataTable({
                processing: "Loading...",
                serverSide: true,
                ajax: "{{ route('production.bom.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'bom_name',
                        name: 'bom_name'
                    },
                    {
                        data: 'part_no',
                        name: 'part_no'
                    },
                    {
                        data: 'part_name',
                        name: 'part_name'
                    },
                    {
                        data: 'bom_active_date',
                        name: 'bom_active_date'
                    },
                    {
                        data: 'bom_status',
                        name: 'bom_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            // Delete handler
            $('#bomTable').on('click', '.btn-delete', function(e) {
                e.preventDefault();
                var bomId = $(this).data('id');

                if (confirm('Apakah Anda yakin ingin menghapus BOM ini?')) {
                    $.ajax({
                        url: "{{ route('production.bom.index') }}/" + bomId,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.message);
                                table.ajax.reload(null, false);
                            } else {
                                alert('Gagal menghapus BOM: ' + response.message);
                            }
                        },
                        error: function(xhr) {
                            var errorMsg = 'Terjadi kesalahan saat menghapus BOM';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg += ': ' + xhr.responseJSON.message;
                            }
                            alert(errorMsg);
                        }
                    });
                }
            });
        });
    </script>
@endpush