@extends('layouts.app')

@section('title', 'Production Monitoring')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Start Production</h3>
                        </div>
                        <form id="productionForm">
                            @csrf
                            <div class="card-body">
                                <!-- Row 1: WO No, WO Qty, Process, Cycle Time -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wo_no">WO No <span class="text-danger">*</span></label>
                                            <select class="form-control" id="wo_no" name="wo_no" required>
                                                <option value="">Select WO No</option>
                                                @foreach ($workOrders as $wo)
                                                    <option value="{{ $wo->wo_no }}">{{ $wo->wo_no }} -
                                                        {{ $wo->part_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wo_qty">WO Qty</label>
                                            <input type="text" class="form-control" id="wo_qty" name="wo_qty"
                                                readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="process_id">Process <span class="text-danger">*</span></label>
                                            <select class="form-control" id="process_id" name="process_id" required
                                                disabled>
                                                <option value="">Select Process</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="cycle_time">Cycle Time (s)</label>
                                            <input type="text" class="form-control" id="cycle_time" name="cycle_time"
                                                readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Row 2: Supervisor, Operator, Machine, Shift -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="supervisor">Supervisor <span class="text-danger">*</span></label>
                                            <select class="form-control" id="supervisor" name="supervisor" required>
                                                <option value="">Select Supervisor</option>
                                                @foreach ($supervisors as $supervisor)
                                                    <option value="{{ $supervisor->name }}">{{ $supervisor->nik }} -
                                                        {{ $supervisor->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="operator">Operator <span class="text-danger">*</span></label>
                                            <select class="form-control" id="operator" name="operator" required>
                                                <option value="">Select Operator</option>
                                                @foreach ($operators as $operator)
                                                    <option value="{{ $operator->name }}">{{ $operator->nik }} -
                                                        {{ $operator->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="machine_id">Machine <span class="text-danger">*</span></label>
                                            <select class="form-control" id="machine_id" name="machine_id" required>
                                                <option value="">Select Machine</option>
                                                @foreach ($machines as $machine)
                                                    <option value="{{ $machine->id }}">{{ $machine->machine_code }} -
                                                        {{ $machine->machine_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="shift_id">Shift <span class="text-danger">*</span></label>
                                            <select class="form-control" id="shift_id" name="shift_id" required>
                                                <option value="">Select Shift</option>
                                                @foreach ($shifts as $shift)
                                                    <option value="{{ $shift->shift_id }}">{{ $shift->shift_name }}
                                                        ({{ $shift->start_time }} - {{ $shift->end_time }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play"></i> Start Production
                                </button>
                                <button type="reset" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            // When WO No is selected
            $('#wo_no').on('change', function() {
                const woNo = $(this).val();

                if (!woNo) {
                    resetForm();
                    return;
                }

                // Get WO Details
                $.ajax({
                    url: '{{ route('production.production-monitoring.wo-details') }}',
                    type: 'GET',
                    data: {
                        wo_no: woNo
                    },
                    success: function(response) {
                        $('#wo_qty').val(response.wo_qty);
                        loadProcesses(woNo);
                    },
                    error: function(xhr) {
                        alert('Error loading WO details');
                        resetForm();
                    }
                });
            });

            // Load processes based on WO
            function loadProcesses(woNo) {
                $.ajax({
                    url: '{{ route('production.production-monitoring.process-list') }}',
                    type: 'GET',
                    data: {
                        wo_no: woNo
                    },
                    success: function(response) {
                        const processSelect = $('#process_id');
                        processSelect.empty();
                        processSelect.append('<option value="">-- Select Process --</option>');

                        response.processes.forEach(function(process) {
                            processSelect.append(
                                `<option value="${process.process_id}" data-cycle="${process.cycle_time}">
                                    ${process.process_name} - ${process.process_desc}
                                </option>`
                            );
                        });

                        processSelect.prop('disabled', false);
                    },
                    error: function(xhr) {
                        alert('Error loading processes');
                    }
                });
            }

            // When Process is selected
            $('#process_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const cycleTime = selectedOption.data('cycle');
                $('#cycle_time').val(cycleTime || '');
            });

            // Form submission
            $('#productionForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();

                $.ajax({
                    url: '{{ route('production.production-monitoring.start') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success && response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            alert('Production started successfully!');
                            resetForm();
                        }
                    },
                    error: function(xhr) {
                        alert('Error starting production: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                    }
                });
            });

            // Reset form
            function resetForm() {
                $('#wo_qty').val('');
                $('#cycle_time').val('');
                $('#process_id').empty().append('<option value="">-- Select Process --</option>').prop('disabled',
                    true);
            }

            // Reset button
            $('button[type="reset"]').on('click', function() {
                resetForm();
            });
        });
    </script>
@endpush
