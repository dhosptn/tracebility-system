<script>
    $(function() {
        var table = $('#lotTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('production.lot_number.index') }}",
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'lot_no',
                    name: 'lot_no',
                    render: function(data, type, row) {
                        return '<a href="javascript:void(0)" class="lot-details" data-id="' +
                            row.lot_id + '">' + data + '</a>';
                    }
                },
                {
                    data: 'lot_date',
                    name: 'lot_date'
                },
                {
                    data: 'qty_per_lot',
                    name: 'qty_per_lot',
                    className: 'text-right',
                    render: function(data, type, row) {
                        // Format angka di client side juga
                        if (data === null || data === undefined) return '0';

                        var num = parseFloat(data);
                        if (isNaN(num)) return '0';

                        // Cek jika bilangan bulat
                        if (Math.floor(num) === num) {
                            return num.toLocaleString('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        } else {
                            return num.toLocaleString('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                },
                {
                    data: 'item_desc',
                    name: 'item_desc',
                    defaultContent: '-'
                },
                {
                    data: 'charge_no',
                    name: 'charge_no',
                    defaultContent: '-'
                },
                {
                    data: 'lot_create_by',
                    name: 'lot_create_by',
                    defaultContent: '-'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        // Pastikan action buttons ada
                        return data;
                    }
                }
            ],
            order: [
                [1, 'desc']
            ],
            pageLength: 10,
            // Tambahkan ini untuk auto-refresh
            drawCallback: function(settings) {
                console.log('Table redrawn at:', new Date());
            }
        });

        // Delete handler - Sudah benar
        $('#lotTable').on('click', '.btn-delete', function() {
            var lotId = $(this).data('id');
            var $row = $(this).closest('tr');

            if (confirm('Are you sure you want to delete this lot?')) {
                $.ajax({
                    url: "{{ route('production.lot_number.destroy', ':id') }}".replace(':id', lotId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Lot deleted successfully');
                            // Refresh tabel
                            table.ajax.reload(null,
                                false); // false berarti tetap di halaman yang sama

                            // Juga refresh modal report jika terbuka
                            if ($('#lotReportModal').is(':visible')) {
                                var currentLotId = $('#modal_lot_no').data('lot-id');
                                if (currentLotId == lotId) {
                                    $('#lotReportModal').modal('hide');
                                }
                            }
                        } else {
                            alert('Failed to delete lot');
                        }
                    },
                    error: function(xhr) {
                        alert('Error deleting lot');
                    }
                });
            }
        });

        // Lot Details Click Handler
        $('#lotTable').on('click', '.lot-details', function() {
            var lotId = $(this).data('id');

            // Show modal with loading state
            $('#report-loading').removeClass('d-none');
            $('#report-content').addClass('d-none');
            $('#lotReportModal').modal('show');

            // Fetch lot details and transactions with cache busting
            $.ajax({
                url: "{{ route('production.wo_report.lot-details', ':id') }}".replace(':id', lotId),
                type: "GET",
                dataType: "json",
                cache: false, // Disable cache to always get fresh data
                data: {
                    _t: new Date().getTime() // Cache buster
                },
                success: function(response) {
                    if (response.success) {
                        var lot = response.data.lot;
                        var transactions = response.data.transactions;

                        // Fill Header Info
                        $('#modal_lot_no').text(lot.lot_no || '-').data('lot-id', lot
                            .lot_id);

                        // Display BOM data
                        $('#modal_lot_date').text(lot.part_no || '-'); // Part No from BOM
                        $('#modal_item_desc').text(lot.part_name ||
                            '-'); // Part Name from BOM

                        // Format qty_per_lot untuk display
                        var qty = parseFloat(lot.qty_per_lot) || 0;
                        var formattedQty = (Math.floor(qty) === qty) ?
                            qty.toLocaleString('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }) :
                            qty.toLocaleString('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        $('#modal_qty_per_lot').text(formattedQty);

                        $('#modal_charge_no').text(lot.charge_no || '-');
                        $('#modal_created_by').text(lot.lot_create_by || '-');

                        $('#modal_section_header').text(lot.item_desc ||
                            'Menggunakan Material');

                        // Fill Table
                        var tbody = $('#modal_report_tbody');
                        tbody.empty();

                        if (transactions.length > 0) {
                            $.each(transactions, function(index, trx) {
                                var row = `
                  <tr>
                    <td>${index + 1}</td>
                    <td>${trx.wo_no || '-'}</td>
                    <td class="text-left">${trx.process_name}</td>
                    <td>${trx.machine || '-'}</td>
                    <td>${trx.prod_date}</td>
                    <td>${parseFloat(trx.total_qty)}</td>
                    <td>${parseFloat(trx.cum_total)}</td>
                    <td>${trx.shift || '-'}</td>
                    <td>${parseFloat(trx.ng_qty)}</td>
                    <td>${parseFloat(trx.cum_ng)}</td>
                    <td>${parseFloat(trx.ok_qty)}</td>
                    <td>${parseFloat(trx.cum_ok)}</td>
                    <td>${trx.operator || '-'}</td>
                    <td>${trx.prod_date}</td>
                    <td>${parseFloat(trx.ambil_qty)}</td>
                    <td>${parseFloat(trx.sisa_qty)}</td>
                    <td>${trx.remark || ''}</td>
                  </tr>
                `;
                                tbody.append(row);
                            });
                        } else {
                            tbody.append(
                                '<tr><td colspan="17" class="text-center py-3">No transaction data found for this Lot.</td></tr>'
                            );
                        }

                        // Show Report
                        $('#report-loading').addClass('d-none');
                        $('#report-content').removeClass('d-none');

                    } else {
                        alert('Error: ' + response.message);
                        $('#lotReportModal').modal('hide');
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    alert('Failed to fetch Lot details');
                    $('#lotReportModal').modal('hide');
                }
            });
        });

        // ========== TAMBAHKAN INI ==========
        // Refresh tabel setelah edit/update
        $(document).on('lotUpdated', function(e, lotId) {
            console.log('Lot updated event received for ID:', lotId);
            table.ajax.reload(null, false);

            // Jika modal report terbuka untuk lot yang sama, refresh juga
            var currentLotId = $('#modal_lot_no').data('lot-id');
            if (currentLotId && currentLotId == lotId) {
                $('.lot-details[data-id="' + lotId + '"]').trigger('click');
            }
        });

        // Auto-refresh periodik (opsional, setiap 30 detik)
        setInterval(function() {
            console.log('Auto-refreshing table...');
            table.ajax.reload(null, false);
        }, 30000); // 30 detik

        // Tangkap event dari form edit (jika menggunakan modal edit)
        $('#editLotModal').on('hidden.bs.modal', function(e) {
            // Refresh setelah modal edit ditutup
            setTimeout(function() {
                table.ajax.reload(null, false);
            }, 500);
        });
    });
</script>
