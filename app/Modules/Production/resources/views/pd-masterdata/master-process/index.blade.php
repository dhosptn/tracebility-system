@extends('layouts.app')

@section('title', 'Master Process')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Master Process List</h3>
                    <div class="card-tools">
                        <a href="{{ route('production.master-process.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Process
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
                        <table id="processTable" class="table table-bordered table-striped table-hover text-sm"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Process Name</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
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
            var table = $('#processTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('production.master-process.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'process_name',
                        name: 'process_name'
                    },
                    {
                        data: 'process_desc',
                        name: 'process_desc',
                        defaultContent: '-'
                    },
                    {
                        data: 'input_by',
                        name: 'input_by'
                    },
                    {
                        data: 'input_time',
                        name: 'input_time'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                pageLength: 10,
                language: {
                    processing: "Loading...",
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });

            // Delete handler
            $('#processTable').on('click', '.btn-delete', function(e) {
                e.preventDefault();
                var processId = $(this).data('id');

                if (confirm('Apakah Anda yakin ingin menghapus process ini?')) {
                    $.ajax({
                        url: "{{ route('production.master-process.index') }}/" + processId,
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
                                alert('Gagal menghapus process: ' + response.message);
                            }
                        },
                        error: function(xhr) {
                            var errorMsg = 'Terjadi kesalahan saat menghapus process';
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
