<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Monitoring TV Display</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white h-screen">

    <!-- Header Bar - Fixed Height -->
    <div class="bg-slate-800/90 backdrop-blur-sm border-b border-cyan-500/30 px-6 py-2 shadow-xl h-[60px]">
        <div class="h-full grid grid-cols-3 items-center">
            <!-- Left: Current Info -->
            <div class="text-xl font-bold text-cyan-400 tracking-wide justify-self-start">CURRENT INFO</div>

            <!-- Center: Monitoring -->
            <div class="text-xl font-bold text-purple-400 tracking-wide text-center justify-self-center">
                MONITORING
            </div>

            <!-- Right: Clock - Jam dan tanggal vertikal center -->
            <div class="justify-self-end">
                <div class="bg-slate-700/50 px-6 py-1.5 rounded-xl flex items-center gap-4">
                    <div id="clock" class="text-2xl font-bold text-cyan-400 tracking-wider leading-tight">00:00:00
                    </div>
                    <div id="date" class="text-sm text-slate-400 font-medium leading-tight">14 Dec 2025</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container - Calculated Height -->
    <div class="h-[calc(100vh-60px)] p-3 grid grid-cols-12 gap-3">

        <!-- LEFT PANEL - 4 columns -->
        <div class="col-span-3 grid grid-rows-12 gap-3">

            <!-- Production Info Card - 6 rows -->
            <div
                class="row-span-3 bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl shadow-2xl border border-slate-700/50 p-4 overflow-hidden">
                <div class="space-y-1.5">
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Lot</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="lot">{{ $monitoring->workOrder->lot->lot_no ?? '-' }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Work Order</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="wo">{{ $monitoring->wo_no }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Lot Quantity</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="lotQty">{{ $monitoring->wo_qty }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Previous Qty</span>
                        <span class="font-bold text-white text-lg uppercase" id="prevQty">0</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Machine</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="machine">{{ $monitoring->machine->machine_name ?? '-' }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Operator</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="operator">{{ $monitoring->operator }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Supervisor</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="supervisor">{{ $monitoring->supervisor }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Shift</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="shift">{{ $monitoring->shift->shift_name ?? '-' }}</span>
                    </div>
                    <div
                        class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Item Number</span>
                        <span class="font-bold text-white text-lg uppercase"
                            id="itemNo">{{ $monitoring->workOrder->part_no ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 hover:bg-slate-700/30 px-2 rounded transition-all">
                        <span class="text-slate-400 font-medium text-lg uppercase">Item Description</span>
                        <span class="font-bold text-white truncate ml-4 text-lg uppercase"
                            id="itemDesc">{{ $monitoring->workOrder->part_name ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div class="row-span-1 flex gap-3">
                <!-- Estimated Finish - setengah lebar -->
                <div
                    class="w-1/2 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl shadow-2xl p-4 flex flex-col justify-center">
                    <div class="text-2xl font-bold text-white tracking-wide mb-1 text-center">ESTIMATED FINISH</div>
                    <div id="finishTime" class="text-xl font-bold text-white text-center">Calculating...</div>
                </div>

                <!-- Machine Status - setengah lebar -->
                <div
                    class="w-1/2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl shadow-2xl border border-slate-700/50 p-4 flex flex-col justify-center">
                    <div class="text-xl font-bold text-cyan-400 tracking-wide mb-2 text-center">MACHINE STATUS</div>
                    <div class="flex items-center justify-center h-full">
                        <div id="statusBadge"
                            class="bg-gradient-to-r from-green-500 to-emerald-400 px-6 py-2 rounded-xl shadow-xl">
                            <div class="text-xl font-black text-white tracking-widest">RUN</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT PANEL - 8 columns -->
        <div class="col-span-9 grid grid-rows-12 gap-3">

            <!-- KPI Cards - 3 rows -->
            <div class="row-span-1 grid grid-cols-4 gap-3">
                <!-- Target Qty -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 flex flex-col items-center justify-center">
                    <div class="text-xl font-semibold text-blue-100 mb-2 tracking-wide text-center w-full">TARGET QTY
                    </div>
                    <div id="targetQty" class="text-5xl font-black text-white mb-1 leading-none">
                        {{ $monitoring->wo_qty }}</div>
                    <div class="text-xl text-blue-100 font-semibold text-center w-full">PCS</div>
                </div>

                <!-- Actual Qty -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 flex flex-col items-center justify-center">
                    <div class="text-xl font-semibold text-green-100 mb-2 tracking-wide text-center w-full">ACTUAL QTY
                    </div>
                    <div id="actualQty" class="text-5xl font-black text-white mb-1 leading-none">
                        {{ $monitoring->qty_actual }}</div>
                    <div class="text-xl text-green-100 font-semibold text-center w-full">PCS</div>
                </div>

                <!-- NG Qty -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 flex flex-col items-center justify-center">
                    <div class="text-xl font-semibold text-red-100 mb-2 tracking-wide text-center w-full">NG QTY</div>
                    <div id="ngQty" class="text-5xl font-black text-white mb-1 leading-none">
                        {{ $monitoring->qty_ng }}</div>
                    <div class="text-xl text-red-100 font-semibold text-center w-full">PCS</div>
                </div>

                <!-- Progress -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 flex flex-col items-center justify-center">
                    <div class="text-xl font-semibold text-amber-100 mb-2 tracking-wide text-center w-full">PROGRESS
                    </div>
                    <div id="progressPercent" class="text-5xl font-black text-white mb-1 leading-none text-center">
                        {{ $monitoring->wo_qty > 0 ? number_format(($monitoring->qty_ok / $monitoring->wo_qty) * 100, 1, '.', '') : 0 }}%
                    </div>
                    <div class="text-xl text-amber-100 font-semibold text-center w-full">
                        {{ $monitoring->qty_ok }}/{{ $monitoring->wo_qty }}
                    </div>
                </div>
            </div>

            <!-- Cycle Time Section - 3 rows -->
            <div class="row-span-1 grid grid-cols-5 gap-3">
                <!-- STD Cycle Time -->
                <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 flex flex-col">
                    <div class="bg-slate-600/50 text-center py-2 mb-2 rounded">
                        <div class="text-xl text-slate-300 font-bold tracking-wide">STD CYCLE TIME</div>
                    </div>
                    <div class="flex-1 flex flex-col items-center justify-center">
                        <div id="stdCycleTime" class="text-5xl font-black text-white mb-1">
                            {{ $monitoring->cycle_time }}</div>
                        <div class="text-xl text-slate-400 font-semibold">SECOND</div>
                    </div>
                </div>

                <!-- Actual Cycle Time Section -->
                <div
                    class="col-span-4 bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 flex flex-col">
                    <div class="bg-slate-600/50 text-center py-2 mb-2 rounded">
                        <div class="text-xl text-slate-300 font-bold tracking-wide">ACTUAL CYCLE TIME</div>
                    </div>
                    <div class="flex-1 grid grid-cols-4 gap-3">
                        <!-- Average -->
                        <div class="flex flex-col items-center justify-center">
                            <div class="text-xl text-slate-400 mb-2 font-semibold">AVERAGE</div>
                            <div id="avgCycleTime" class="text-5xl font-black text-white mb-1">0</div>
                            <div class="text-xl text-slate-400 font-semibold">SECOND</div>
                        </div>
                        <!-- Last Piece -->
                        <div class="flex flex-col items-center justify-center">
                            <div class="text-xl text-slate-400 mb-2 font-semibold">LAST PIECE</div>
                            <div id="lastCycleTime" class="text-5xl font-black text-white mb-1">0</div>
                            <div class="text-xl text-slate-400 font-semibold">SECOND</div>
                        </div>
                        <!-- High -->
                        <div class="flex flex-col items-center justify-center">
                            <div class="text-xl text-slate-400 mb-2 font-semibold">HIGH</div>
                            <div id="highCycleTime" class="text-5xl font-black text-white mb-1">0</div>
                            <div class="text-xl text-slate-400 font-semibold">SECOND</div>
                        </div>
                        <!-- Low -->
                        <div class="flex flex-col items-center justify-center">
                            <div class="text-xl text-slate-400 mb-2 font-semibold">LOW</div>
                            <div id="lowCycleTime" class="text-5xl font-black text-white mb-1">0</div>
                            <div class="text-xl text-slate-400 font-semibold">SECOND</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OEE Section - 3 rows -->
            <div class="row-span-1 grid grid-cols-5 gap-3">
                <!-- OEE -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 text-center flex flex-col justify-center">
                    <div class="text-xl font-semibold text-purple-100 mb-2 tracking-wide">OEE</div>
                    <div id="oee" class="text-5xl font-black text-white mb-1">0</div>
                    <div class="text-xl text-purple-100 font-semibold">%</div>
                </div>

                <!-- Availability -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 text-center flex flex-col justify-center">
                    <div class="text-xl font-semibold text-blue-100 mb-2 tracking-wide">AVAILABILITY</div>
                    <div id="availability" class="text-5xl font-black text-white mb-1">0</div>
                    <div class="text-xl text-blue-100 font-semibold">%</div>
                </div>

                <!-- Performance -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 text-center flex flex-col justify-center">
                    <div class="text-xl font-semibold text-green-100 mb-2 tracking-wide">PERFORMANCE</div>
                    <div id="performance" class="text-5xl font-black text-white mb-1">0</div>
                    <div class="text-xl text-green-100 font-semibold">%</div>
                </div>

                <!-- Quality -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 text-center flex flex-col justify-center">
                    <div class="text-xl font-semibold text-yellow-100 mb-2 tracking-wide">QUALITY</div>
                    <div id="quality" class="text-5xl font-black text-white mb-1">0</div>
                    <div class="text-xl text-yellow-100 font-semibold">%</div>
                </div>

                <!-- Uptime -->
                <div
                    class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-3 text-center flex flex-col justify-center">
                    <div class="text-xl font-semibold text-emerald-100 mb-2 tracking-wide">UPTIME</div>
                    <div id="uptime" class="text-5xl font-black text-white mb-1">0</div>
                    <div class="text-xl text-emerald-100 font-semibold">%</div>
                </div>
            </div>

            <!-- Timeline Chart - 3 rows -->
            <div
                class="row-span-1 bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl shadow-2xl border border-slate-700/50 p-4 flex flex-col">
                <div class="text-2xl font-bold text-indigo-400 tracking-wide mb-3">MACHINE STATUS TIMELINE</div>

                <!-- Timeline Content -->
                <div class="flex-1 flex flex-col gap-3">
                    <!-- Timeline Container -->
                    <div class="flex-1 flex flex-col gap-2">
                        <!-- Status Bars - Clean design with only colors -->
                        <div
                            class="relative h-12 bg-slate-700/30 rounded-lg border border-slate-600/50 overflow-hidden shadow-inner">
                            <!-- Status Bars -->
                            <div id="timelineVisual" class="absolute inset-0 flex">
                                <!-- Default placeholder -->
                                <div class="flex-1 bg-slate-600/50"></div>
                            </div>
                        </div>

                        <!-- Time Labels with markers -->
                        <div class="relative">
                            <div id="timeLabels"
                                class="flex justify-between text-sm text-slate-300 font-semibold px-1">
                                <div class="flex flex-col items-center">
                                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                    <span>08:00</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                    <span>09:00</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                    <span>10:00</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                    <span>11:00</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                    <span>12:00</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                    <span>13:00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex gap-8 justify-center pt-2 border-t border-slate-700/50">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-500 rounded shadow-lg"></div>
                            <span class="text-slate-300 text-lg font-semibold">Running</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-red-500 rounded shadow-lg"></div>
                            <span class="text-slate-300 text-lg font-semibold">Downtime</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 bg-yellow-500 rounded shadow-lg"></div>
                            <span class="text-slate-300 text-lg font-semibold">Ready</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- NG Modal -->
    <div id="ngModal"
        class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
        <div
            class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-2xl border border-slate-700 p-8 w-96">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                <h2 class="text-2xl font-bold text-white">Record NG</h2>
            </div>
            <form id="ngForm" class="space-y-4">
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">NG Type</label>
                    <select name="ng_type" required
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-red-500 focus:outline-none">
                        <option value="">-- Select Type --</option>
                        <option value="Material">Material</option>
                        <option value="Process">Process</option>
                        <option value="Machine">Machine</option>
                        <option value="Human Error">Human Error</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">NG Reason</label>
                    <input type="text" name="ng_reason" required
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">Quantity</label>
                    <input type="number" name="qty" min="1" value="1" required
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-red-500 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeNgModal()"
                        class="flex-1 bg-slate-600 hover:bg-slate-700 text-white font-bold py-3 rounded-lg transition-colors">Cancel</button>
                    <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition-colors">Save
                        NG</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Downtime Modal -->
    <div id="downtimeModal"
        class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
        <div
            class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-2xl border border-slate-700 p-8 w-96">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-tools text-yellow-500 text-3xl"></i>
                <h2 class="text-2xl font-bold text-white">Record Downtime</h2>
            </div>
            <form id="downtimeForm" class="space-y-4">
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">Downtime Type</label>
                    <select name="downtime_type" required
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-yellow-500 focus:outline-none">
                        <option value="">-- Select Type --</option>
                        <option value="Machine Breakdown">Machine Breakdown</option>
                        <option value="Material Shortage">Material Shortage</option>
                        <option value="Tool Change">Tool Change</option>
                        <option value="Setup">Setup</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">Downtime Reason</label>
                    <input type="text" name="downtime_reason" required
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-yellow-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-300 font-semibold mb-2">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full bg-slate-700 text-white rounded-lg px-4 py-3 border border-slate-600 focus:border-yellow-500 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeDowntimeModal()"
                        class="flex-1 bg-slate-600 hover:bg-slate-700 text-white font-bold py-3 rounded-lg transition-colors">Cancel</button>
                    <button type="submit"
                        class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-3 rounded-lg transition-colors">Save
                        Downtime</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('js/tv-display.js') }}"></script>
    <script>
        // Initialize TV Display with monitoring data
        $(document).ready(function() {
            window.initTvDisplay({{ $monitoring->monitoring_id }}, {
                qty_actual: {{ $monitoring->qty_actual }},
                qty_ng: {{ $monitoring->qty_ng }},
                qty_ok: {{ $monitoring->qty_ok }},
                wo_qty: {{ $monitoring->wo_qty }},
                cycle_time: {{ $monitoring->cycle_time }}
            });
        });
    </script>
</body>

</html>
