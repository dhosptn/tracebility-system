@extends('layouts.app')

@section('title', 'Work Order Transaction')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-1"></i>
                        Work Order Transaction List
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('production.wo-transaction.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create New Transaction
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover text-sm" id="wo-transaction-table"
                        style="width: 100%">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>WO No</th>
                                <th>Item</th>
                                <th>Process Name</th>
                                <th>Total Qty OK</th>
                                <th>Total Qty NG</th>
                                <th>Prod. Time</th>
                                <th>Downtime</th>
                                <th>OEE</th>
                                <th>WO Date</th>
                                <th>Production Date</th>
                                <th width="10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            var table = $('#wo-transaction-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('production.wo-transaction.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'wo_no',
                        name: 'wo_no'
                    },
                    {
                        data: 'item',
                        name: 'item'
                    },
                    {
                        data: 'process_name',
                        name: 'process_name'
                    },
                    {
                        data: 'ok_qty',
                        name: 'ok_qty',
                        className: 'text-right'
                    },
                    {
                        data: 'ng_qty',
                        name: 'ng_qty',
                        className: 'text-right'
                    },
                    {
                        data: 'prod_time',
                        name: 'prod_time',
                        className: 'text-center'
                    },
                    {
                        data: 'downtime',
                        name: 'downtime',
                        className: 'text-center'
                    },
                    {
                        data: 'oee',
                        name: 'oee',
                        className: 'text-center'
                    },
                    {
                        data: 'wo_date',
                        name: 'wo_date',
                        className: 'text-center'
                    },
                    {
                        data: 'prod_date',
                        name: 'prod_date',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                autoWidth: false,
                language: {
                    processing: "Loading...",
                }
            });

            // Delete button handler
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url = $(this).data('url');
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                                table.ajax.reload();
                            },
                            error: function(xhr, status, error) {
                                console.error('Delete Error:', xhr.responseText);
                                var message = 'Failed to delete transaction.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire(
                                    'Error!',
                                    message,
                                    'error'
                                );
                            }
                        });
                    }
                });
            });

            // Success/Error messages
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '{{ session('success') }}',
                    timer: 3000
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    timer: 3000
                });
            @endif
        });
    </script>
@endpush
