<!-- Control Lot Table Modal -->
<div class="modal fade" id="lotReportModal" tabindex="-1" role="dialog" aria-labelledby="lotReportModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lotReportModalLabel">
                    <i class="fas fa-file-alt mr-2"></i>Control Lot Table
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <!-- Loading State -->
                <div id="report-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading Report Data...</p>
                </div>

                <!-- Report Container -->
                <div class="report-wrapper d-none" id="report-content">
                    <style>
                        .report-container {
                            font-family: 'Arial', sans-serif;
                            color: #333;
                            background: white;
                            padding: 20px;
                            min-width: 1000px;
                            overflow-x: auto;
                        }

                        .report-title {
                            text-align: center;
                            font-size: 24px;
                            font-weight: bold;
                            text-transform: uppercase;
                            margin-bottom: 25px;
                            text-decoration: underline;
                            letter-spacing: 1px;
                        }

                        .info-box-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 10px;
                            font-size: 13px;
                        }

                        .info-box-table td {
                            border: 1px solid #000;
                            padding: 6px 10px;
                            vertical-align: middle;
                        }

                        .info-label {
                            font-weight: bold;
                            background-color: #f4f6f9;
                            width: 140px;
                        }

                        .spacer-row {
                            height: 10px;
                        }

                        .section-header {
                            width: 100%;
                            border: 1px solid #000;
                            background-color: #e9ecef;
                            text-align: center;
                            font-weight: bold;
                            font-size: 16px;
                            padding: 8px;
                            margin: 15px 0 10px 0;
                            text-transform: uppercase;
                        }

                        .report-data-table {
                            width: 100%;
                            border-collapse: collapse;
                            font-size: 12px;
                        }

                        .report-data-table th,
                        .report-data-table td {
                            border: 1px solid #000;
                            text-align: center;
                            padding: 6px;
                            vertical-align: middle;
                        }

                        .report-data-table th {
                            background-color: #f4f6f9;
                            font-weight: bold;
                            text-transform: uppercase;
                        }

                        .text-right {
                            text-align: right !important;
                        }

                        .text-left {
                            text-align: left !important;
                        }
                    </style>

                    <div class="report-container">
                        <div class="report-title">Control Lot Table</div>

                        <!-- Header Info -->
                        <div class="row">
                            <div class="col-md-6 pr-md-3">
                                <table class="info-box-table">
                                    <tr>
                                        <td class="info-label">PART NO</td>
                                        <td><span id="modal_lot_date"></span></td>
                                    </tr>
                                </table>
                                <div class="spacer-row"></div>
                                <table class="info-box-table">
                                    <tr>
                                        <td class="info-label">PART NAME</td>
                                        <td><span id="modal_item_desc"></span></td>
                                    </tr>
                                </table>
                                <div class="spacer-row"></div>
                                <table class="info-box-table">
                                    <tr>
                                        <td class="info-label">CREATED BY</td>
                                        <td><span id="modal_created_by"></span></td>
                                    </tr>
                                </table>
                                <div class="spacer-row"></div>
                            </div>

                            <div class="col-md-6 pl-md-3">
                                <table class="info-box-table">
                                    <tr>
                                        <td class="info-label">CHARGE NO.</td>
                                        <td><span id="modal_charge_no"></span></td>
                                    </tr>
                                </table>
                                <div class="spacer-row"></div>
                                <table class="info-box-table">
                                    <tr>
                                        <td class="info-label">LOT NO.</td>
                                        <td><span id="modal_lot_no"></span></td>
                                    </tr>
                                </table>
                                <div class="spacer-row"></div>
                                <table class="info-box-table">
                                    <tr>
                                        <td class="info-label">TOTAL / LOT</td>
                                        <td><span id="modal_qty_per_lot"></span> <span
                                                class="float-right font-weight-bold">PCS</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="section-header" id="modal_section_header">
                            </div>

                            <table class="report-data-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2" style="width: 40px;">No</th>
                                        <th rowspan="2">WO No</th>
                                        <th rowspan="2">Nama Proses</th>
                                        <th rowspan="2">Pos Produksi</th>
                                        <th rowspan="2">Tanggal Produksi</th>
                                        <th colspan="2">Produksi</th>
                                        <th rowspan="2">Shift</th>
                                        <th colspan="2">NG Part</th>
                                        <th colspan="2">OK Part</th>
                                        <th rowspan="2">Operator</th>
                                        <th colspan="3">Jika Produksi Terbagi</th>
                                        <th rowspan="2">Remark</th>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        <th>Kumulatif</th>
                                        <th>QTY</th>
                                        <th>Kumulatif</th>
                                        <th>QTY</th>
                                        <th>Kumulatif</th>
                                        <th>Tanggal</th>
                                        <th>Ambil</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody id="modal_report_tbody">
                                    <!-- Data will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printLotReport()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
    function printLotReport() {
        var content = document.getElementById('report-content');
        if (!content) return;

        var win = window.open('', '_blank');
        
        var html = '<!DOCTYPE html><html><head><title>Control Lot Report</title>';
        
        // Copy styles
        var styles = document.querySelectorAll('link[rel="stylesheet"], style');
        styles.forEach(function(node) {
            html += node.outerHTML;
        });
        
        html += '<style>';
        html += '@media print {';
        html += '   @page { size: landscape; margin: 10mm; }';
        html += '   body { -webkit-print-color-adjust: exact; margin: 0; }';
        html += '   .report-container { width: 100% !important; min-width: 100% !important; margin: 0; padding: 0; box-shadow: none !important; }';
        html += '   .col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; }';
        html += '}';
        html += 'body { background-color: #fff; }';
        html += '.report-container { width: 100% !important; min-width: 100% !important; box-shadow: none !important; }';
        html += '.col-md-6 { flex: 0 0 50% !important; max-width: 50% !important; }';
        html += '</style>';
        
        html += '</head><body>';
        html += content.innerHTML;
        
        // Auto print
        html += '<script>';
        html += 'window.onload = function() { setTimeout(function() { window.print(); }, 1000); };';
        html += '<' + '/script>';
        
        html += '</body></html>';
        
        win.document.write(html);
        win.document.close();
        win.focus();
    }
</script>
