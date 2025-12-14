<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Monitoring - {{ $monitoring->wo_no }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #1a1a1a;
            color: #fff;
            font-family: 'Arial', sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .status-card {
            background: #2d2d2d;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid #444;
        }

        .status-running {
            border-color: #28a745;
        }

        .status-paused {
            border-color: #ffc107;
        }

        .status-stopped {
            border-color: #dc3545;
        }

        .status-ready {
            border-color: #17a2b8;
        }

        .metric-box {
            background: #3d3d3d;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .metric-label {
            font-size: 0.9rem;
            color: #aaa;
            text-transform: uppercase;
        }

        .btn-status {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            margin-bottom: 10px;
            border-radius: 8px;
        }

        .btn-running {
            background: #28a745;
            color: white;
        }

        .btn-paused {
            background: #ffc107;
            color: black;
        }

        .btn-stopped {
            background: #dc3545;
            color: white;
        }

        .timer-display {
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background: #2d2d2d;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .progress-custom {
            height: 30px;
            font-size: 1rem;
            background: #2d2d2d;
        }

        .log-item {
            background: #3d3d3d;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }

        .modal-content {
            background: #2d2d2d;
            color: #fff;
        }

        .form-control,
        .form-select {
            background: #3d3d3d;
            color: #fff;
            border: 1px solid #555;
        }

        .form-control:focus,
        .form-select:focus {
            background: #3d3d3d;
            color: #fff;
            border-color: #667eea;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-industry"></i> Production Monitoring</h2>
                    <h4>WO: {{ $monitoring->wo_no }} | Process: {{ $monitoring->process_name }}</h4>
                    <p class="mb-0">
                        <i class="fas fa-user-tie"></i> Supervisor: {{ $monitoring->supervisor }} |
                        <i class="fas fa-user"></i> Operator: {{ $monitoring->operator }} |
                        <i class="fas fa-cogs"></i> Machine: {{ $monitoring->machine->machine_name ?? '-' }} |
                        <i class="fas fa-clock"></i> Shift: {{ $monitoring->shift->shift_name ?? '-' }}
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('production.production-monitoring.index') }}" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Status & Controls -->
            <div class="col-md-4">
                <!-- Current Status -->
                <div class="status-card status-{{ strtolower($monitoring->current_status) }}">
                    <h4><i class="fas fa-info-circle"></i> Current Status</h4>
                    <h2 class="text-center my-3" id="currentStatus">{{ $monitoring->current_status }}</h2>
                </div>

                <!-- Timer -->
                <div class="timer-display" id="timerDisplay">
                    00:00:00
                </div>

                <!-- Status Controls -->
                <div class="status-card">
                    <h5><i class="fas fa-play-circle"></i> Status Control</h5>
                    <button class="btn btn-status btn-running" onclick="updateStatus('Running')">
                        <i class="fas fa-play"></i> Start / Running
                    </button>
                    <button class="btn btn-status btn-paused" onclick="updateStatus('Paused')">
                        <i class="fas fa-pause"></i> Pause
                    </button>
                    <button class="btn btn-status btn-stopped" onclick="updateStatus('Stopped')">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                </div>

                <!-- Quick Actions -->
                <div class="status-card">
                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                    <button class="btn btn-success btn-lg w-100 mb-2" onclick="addQtyOk()">
                        <i class="fas fa-plus-circle"></i> Add OK Qty (+1)
                    </button>
                    <button class="btn btn-warning btn-lg w-100 mb-2" data-bs-toggle="modal" data-bs-target="#ngModal">
                        <i class="fas fa-exclamation-triangle"></i> Record NG
                    </button>
                    <button class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#downtimeModal">
                        <i class="fas fa-tools"></i> Record Downtime
                    </button>
                </div>
            </div>

            <!-- Right Column: Metrics & Logs -->
            <div class="col-md-8">
                <!-- Production Metrics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-box">
                            <div class="metric-label">Target Qty</div>
                            <div class="metric-value text-info">{{ $monitoring->wo_qty }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <div class="metric-label">OK Qty</div>
                            <div class="metric-value text-success" id="qtyOk">{{ $monitoring->qty_ok }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <div class="metric-label">NG Qty</div>
                            <div class="metric-value text-danger" id="qtyNg">{{ $monitoring->qty_ng }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <div class="metric-label">Actual Qty</div>
                            <div class="metric-value text-warning" id="qtyActual">{{ $monitoring->qty_actual }}</div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="status-card">
                    <h5><i class="fas fa-chart-line"></i> Production Progress</h5>
                    <div class="progress progress-custom">
                        <div class="progress-bar bg-success" role="progressbar" id="progressBar"
                            style="width: {{ $monitoring->wo_qty > 0 ? ($monitoring->qty_ok / $monitoring->wo_qty) * 100 : 0 }}%">
                            <span
                                id="progressText">{{ $monitoring->wo_qty > 0 ? number_format(($monitoring->qty_ok / $monitoring->wo_qty) * 100, 1) : 0 }}%</span>
                        </div>
                    </div>
                    <p class="text-center mt-2 mb-0">
                        <span id="remainingQty">{{ $monitoring->wo_qty - $monitoring->qty_ok }}</span> units remaining
                    </p>
                </div>

                <!-- Cycle Time Info -->
                <div class="status-card">
                    <h5><i class="fas fa-stopwatch"></i> Cycle Time Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Standard Cycle Time:</strong> {{ $monitoring->cycle_time }} seconds</p>
                            <p><strong>Expected Output/Hour:</strong>
                                {{ $monitoring->cycle_time > 0 ? number_format(3600 / $monitoring->cycle_time, 0) : 0 }}
                                units</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Start Time:</strong> {{ $monitoring->start_time->format('d-m-Y H:i:s') }}</p>
                            <p><strong>End Time:</strong>
                                {{ $monitoring->end_time ? $monitoring->end_time->format('d-m-Y H:i:s') : 'In Progress' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status Logs -->
                <div class="status-card">
                    <h5><i class="fas fa-history"></i> Status History</h5>
                    <div style="max-height: 300px; overflow-y: auto;">
                        @forelse($monitoring->statusLogs as $log)
                            <div class="log-item">
                                <strong>{{ $log->status }}</strong> -
                                {{ $log->start_time->format('H:i:s') }}
                                @if ($log->end_time)
                                    to {{ $log->end_time->format('H:i:s') }}
                                    ({{ gmdate('H:i:s', $log->duration_seconds) }})
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted">No status logs yet</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NG Modal -->
    <div class="modal fade" id="ngModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Record NG</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="ngForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">NG Type</label>
                            <select class="form-select" name="ng_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Material">Material</option>
                                <option value="Process">Process</option>
                                <option value="Machine">Machine</option>
                                <option value="Human Error">Human Error</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">NG Reason</label>
                            <input type="text" class="form-control" name="ng_reason" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="qty" min="1" value="1"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Save NG</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Downtime Modal -->
    <div class="modal fade" id="downtimeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tools"></i> Record Downtime</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="downtimeForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Downtime Type</label>
                            <select class="form-select" name="downtime_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Machine Breakdown">Machine Breakdown</option>
                                <option value="Material Shortage">Material Shortage</option>
                                <option value="Tool Change">Tool Change</option>
                                <option value="Setup">Setup</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Downtime Reason</label>
                            <input type="text" class="form-control" name="downtime_reason" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Save Downtime</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const monitoringId = {{ $monitoring->monitoring_id }};
        let timerInterval;
        let startTime = new Date('{{ $monitoring->start_time }}');

        // Timer
        function updateTimer() {
            const now = new Date();
            const diff = Math.floor((now - startTime) / 1000);
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;

            $('#timerDisplay').text(
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0')
            );
        }

        // Start timer
        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();

        // Update Status
        function updateStatus(status) {
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/update-status`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                success: function(response) {
                    $('#currentStatus').text(status);
                    $('.status-card').first().removeClass(
                            'status-running status-paused status-stopped status-ready')
                        .addClass('status-' + status.toLowerCase());
                    alert('Status updated to ' + status);
                    location.reload();
                },
                error: function() {
                    alert('Error updating status');
                }
            });
        }

        // Add OK Qty
        function addQtyOk() {
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/update-qty-ok`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    qty: 1
                },
                success: function(response) {
                    $('#qtyOk').text(response.qty_ok);
                    $('#qtyActual').text(response.qty_actual);

                    const targetQty = {{ $monitoring->wo_qty }};
                    const progress = (response.qty_ok / targetQty * 100).toFixed(1);
                    $('#progressBar').css('width', progress + '%');
                    $('#progressText').text(progress + '%');
                    $('#remainingQty').text(targetQty - response.qty_ok);
                },
                error: function() {
                    alert('Error updating OK quantity');
                }
            });
        }

        // NG Form Submit
        $('#ngForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/save-ng`,
                type: 'POST',
                data: $(this).serialize() + '&_token={{ csrf_token() }}',
                success: function(response) {
                    $('#qtyNg').text(response.qty_ng);
                    $('#qtyActual').text(response.qty_actual);
                    $('#ngModal').modal('hide');
                    $('#ngForm')[0].reset();
                    alert('NG recorded successfully');
                },
                error: function() {
                    alert('Error recording NG');
                }
            });
        });

        // Check for MQTT signals every 2 seconds
        setInterval(function() {
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/check-mqtt-ng-signal`,
                type: 'GET',
                success: function(response) {
                    if (response.show && response.qty) {
                        // Set qty value from MQTT
                        $('input[name="qty"]').val(response.qty);
                        // Show NG modal
                        const ngModal = new bootstrap.Modal(document.getElementById('ngModal'));
                        ngModal.show();
                    }
                }
            });

            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/check-mqtt-status-signal`,
                type: 'GET',
                success: function(response) {
                    if (response.show && response.status) {
                        $('#currentStatus').text(response.status);
                        $('.status-card').first().removeClass(
                                'status-running status-paused status-stopped status-ready')
                            .addClass('status-' + response.status.toLowerCase());
                        location.reload();
                    }
                }
            });
        }, 2000);

        // Downtime Form Submit
        $('#downtimeForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/save-downtime`,
                type: 'POST',
                data: $(this).serialize() + '&_token={{ csrf_token() }}',
                success: function(response) {
                    $('#downtimeModal').modal('hide');
                    $('#downtimeForm')[0].reset();
                    alert('Downtime recorded successfully');
                    location.reload();
                },
                error: function() {
                    alert('Error recording downtime');
                }
            });
        });

        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>

</html>
