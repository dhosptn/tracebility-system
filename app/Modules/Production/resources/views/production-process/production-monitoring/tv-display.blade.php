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
    <div
        class="bg-slate-800/90 backdrop-blur-sm border-b border-cyan-500/30 px-6 py-2 flex items-center justify-between shadow-xl h-[60px]">
        <div class="flex items-center gap-16">
            <div class="text-xl font-bold text-cyan-400 tracking-wide">CURRENT INFO</div>
            <div class="text-xl font-bold text-purple-400 tracking-wide">MONITORING</div>
        </div>

        <!-- Clock -->
        <div class="bg-slate-700/50 px-6 py-1.5 rounded-xl">
            <div id="clock" class="text-2xl font-bold text-cyan-400 tracking-wider">00:00:00</div>
            <div id="date" class="text-xs text-slate-400 text-center">14 Dec 2025</div>
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
                            id="lot">{{ $monitoring->workOrder->lot_no ?? '-' }}</span>
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
                <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-4 text-center">
                    <div class="text-xl font-semibold text-blue-100 mb-2 tracking-wide">TARGET QTY</div>
                    <div id="targetQty" class="text-5xl font-black text-white mb-1">{{ $monitoring->wo_qty }}</div>
                    <div class="text-xl text-blue-100 font-semibold">PCS</div>
                </div>

                <!-- Actual Qty -->
                <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-4 text-center">
                    <div class="text-xl font-semibold text-green-100 mb-2 tracking-wide">ACTUAL QTY</div>
                    <div id="actualQty" class="text-5xl font-black text-white mb-1">{{ $monitoring->qty_actual }}</div>
                    <div class="text-xl text-green-100 font-semibold">PCS</div>
                </div>

                <!-- NG Qty -->
                <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-4 text-center">
                    <div class="text-xl font-semibold text-red-100 mb-2 tracking-wide">NG QTY</div>
                    <div id="ngQty" class="text-5xl font-black text-white mb-1">{{ $monitoring->qty_ng }}</div>
                    <div class="text-xl text-red-100 font-semibold">PCS</div>
                </div>

                <!-- Progress -->
                <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-xl p-4 text-center">
                    <div class="text-sm font-semibold text-amber-100 mb-2 tracking-wide">PROGRESS</div>
                    <div id="progressPercent" class="text-5xl font-black text-white mb-1">
                        {{ $monitoring->wo_qty > 0 ? number_format(($monitoring->qty_ok / $monitoring->wo_qty) * 100, 1) : 0 }}%
                    </div>
                    <div class="text-xl text-amber-100 font-semibold">
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
                class="row-span-1 bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl shadow-2xl border border-slate-700/50 p-4">
                <div class="text-2xl font-bold text-indigo-400 tracking-wide mb-3">MACHINE STATUS TIMELINE</div>

                <!-- Timeline Content -->
                <div class="flex flex-col gap-2 h-[calc(100%-2rem)]">
                    <!-- Machine Row -->
                    <div class="flex gap-3 items-center flex-1">
                        <div class="w-24">
                            <span class="text-slate-300 font-bold text-sm">MACHINE 1</span>
                        </div>

                        <!-- Timeline Container -->
                        <div class="flex-1 flex flex-col gap-1">
                            <!-- Timeline Bars -->
                            <div
                                class="relative h-10 bg-slate-700/20 rounded border border-slate-600/50 overflow-hidden">
                                <!-- Grid Lines -->
                                <div class="absolute inset-0 flex">
                                    <div class="flex-1 border-r border-slate-600/30"></div>
                                    <div class="flex-1 border-r border-slate-600/30"></div>
                                    <div class="flex-1 border-r border-slate-600/30"></div>
                                    <div class="flex-1 border-r border-slate-600/30"></div>
                                    <div class="flex-1"></div>
                                </div>

                                <!-- Status Bars -->
                                <div id="timelineVisual" class="absolute inset-0 flex">
                                    <div class="flex-[70] bg-green-500 border-r border-slate-700 flex items-center justify-center text-white font-bold text-xs hover:bg-green-400 transition-colors"
                                        title="Running">
                                        <span>RUN</span>
                                    </div>
                                    <div class="flex-[20] bg-red-500 border-r border-slate-700 flex items-center justify-center text-white font-bold text-xs hover:bg-red-400 transition-colors"
                                        title="Downtime">
                                        <span>DOWN</span>
                                    </div>
                                    <div class="flex-[10] bg-yellow-500 flex items-center justify-center text-white font-bold text-xs hover:bg-yellow-400 transition-colors"
                                        title="Ready">
                                        <span>READY</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Time Labels -->
                            <div id="timeLabels" class="flex text-xs text-slate-400 font-semibold">
                                <div class="flex-1 text-left">08:00</div>
                                <div class="flex-1 text-center">09:00</div>
                                <div class="flex-1 text-center">10:00</div>
                                <div class="flex-1 text-center">11:00</div>
                                <div class="flex-1 text-center">12:00</div>
                                <div class="flex-1 text-right">13:00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex gap-6 justify-center pt-1 border-t border-slate-700">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-green-500 border border-slate-700 rounded"></div>
                            <span class="text-slate-300 text-xl font-semibold">Running</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-red-500 border border-slate-700 rounded"></div>
                            <span class="text-slate-300 text-xl font-semibold">Downtime</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-yellow-500 border border-slate-700 rounded"></div>
                            <span class="text-slate-300 text-xl font-semibold">Ready</span>
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
    <script>
        const monitoringId = {{ $monitoring->monitoring_id }};

        // Update Clock and Date (Indonesia Timezone - WIB UTC+7)
        function updateClock() {
            const now = new Date();
            // Convert to Indonesia timezone (WIB - UTC+7)
            const indonesiaTime = new Date(now.toLocaleString('en-US', {
                timeZone: 'Asia/Jakarta'
            }));

            const hours = String(indonesiaTime.getHours()).padStart(2, '0');
            const minutes = String(indonesiaTime.getMinutes()).padStart(2, '0');
            const seconds = String(indonesiaTime.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;

            const dateString = indonesiaTime.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            $('#clock').text(timeString);
            $('#date').text(dateString);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Calculate Estimated Finish Time (Indonesia Timezone - WIB UTC+7)
        function calculateFinishTime(actualQty, targetQty, avgCycleTime) {
            if (actualQty >= targetQty) {
                return "COMPLETED";
            }
            if (avgCycleTime <= 0) {
                return "Calculating...";
            }
            const remainingQty = targetQty - actualQty;
            const remainingSeconds = remainingQty * avgCycleTime;
            const finishTime = new Date(Date.now() + remainingSeconds * 1000);

            // Convert to Indonesia timezone (WIB - UTC+7)
            const indonesiaFinishTime = new Date(finishTime.toLocaleString('en-US', {
                timeZone: 'Asia/Jakarta'
            }));
            const hours = String(indonesiaFinishTime.getHours()).padStart(2, '0');
            const minutes = String(indonesiaFinishTime.getMinutes()).padStart(2, '0');

            return `${hours}:${minutes}`;
        }

        // Update Finish Time
        function updateFinishTime(actualQty, targetQty, avgCycleTime) {
            const finishTime = calculateFinishTime(actualQty, targetQty, avgCycleTime);
            $('#finishTime').text(finishTime);
        }

        // Update Machine Status
        function updateStatus(status) {
            const badge = $('#statusBadge');

            if (status === 'Running') {
                badge.removeClass().addClass(
                    'bg-gradient-to-r from-green-500 to-emerald-400 px-12 py-4 rounded-xl shadow-xl');
                badge.html('<div class="text-2xl font-black text-white tracking-widest">RUN</div>');
            } else if (status === 'Paused' || status === 'Ready') {
                badge.removeClass().addClass(
                    'bg-gradient-to-r from-yellow-500 to-orange-400 px-12 py-4 rounded-xl shadow-xl');
                badge.html('<div class="text2xl font-black text-white tracking-widest">IDLE</div>');
            } else {
                badge.removeClass().addClass('bg-gradient-to-r from-red-500 to-red-600 px-12 py-2 rounded-xl shadow-xl');
                badge.html('<div class="text-2xl font-black text-white tracking-widest">DOWNTIME</div>');
            }
        }

        // Format time to HH:MM:SS
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }

        // Update Timeline Display
        function updateTimeline(timeline) {
            if (!timeline || timeline.length === 0) return;

            const totalDuration = timeline.reduce((sum, item) => sum + (item.duration || 0), 0);
            const maxDuration = Math.max(totalDuration, 3600);

            let visualHtml = '';
            timeline.forEach(item => {
                let colorClass = '';
                let label = '';

                if (item.status === 'Running') {
                    colorClass = 'bg-green-500 hover:bg-green-400';
                    label = 'RUN';
                } else if (item.status === 'Ready') {
                    colorClass = 'bg-yellow-500 hover:bg-yellow-400';
                    label = 'READY';
                } else if (item.status === 'Downtime') {
                    colorClass = 'bg-red-500 hover:bg-red-400';
                    label = 'DOWN';
                } else {
                    colorClass = 'bg-slate-500 hover:bg-slate-400';
                    label = item.status;
                }

                const widthPercent = ((item.duration || 0) / maxDuration) * 100;
                const duration = formatTime(item.duration || 0);

                visualHtml += `
                        <div class="${colorClass} border-r border-slate-700 flex items-center justify-center text-white font-bold text-xs transition-colors" 
                            style="flex: ${Math.max(widthPercent, 1)}" 
                            title="${item.status} - ${duration}">
                            <span>${label}</span>
                        </div>
                    `;
            });

            $('#timelineVisual').html(visualHtml);

            if (timeline.length > 0) {
                const startTime = timeline[0].time || '08:00:00';
                const [hours, minutes] = startTime.split(':').map(Number);
                let currentTime = hours * 60 + minutes;

                let timeLabelsHtml = '';
                for (let i = 0; i < 6; i++) {
                    const h = Math.floor(currentTime / 60) % 24;
                    const m = currentTime % 60;
                    const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;

                    if (i === 0) {
                        timeLabelsHtml += `<div class="flex-1 text-left">${timeStr}</div>`;
                    } else if (i === 5) {
                        timeLabelsHtml += `<div class="flex-1 text-right">${timeStr}</div>`;
                    } else {
                        timeLabelsHtml += `<div class="flex-1 text-center">${timeStr}</div>`;
                    }

                    currentTime += 20;
                }
                $('#timeLabels').html(timeLabelsHtml);
            }
        }

        // Store previous values for change detection
        let previousData = {
            qty_actual: {{ $monitoring->qty_actual }},
            qty_ng: {{ $monitoring->qty_ng }},
            qty_ok: {{ $monitoring->qty_ok }},
            oee: 0,
            availability: 0,
            performance: 0,
            quality: 0,
            uptime: 0,
            avg_cycle_time: {{ $monitoring->cycle_time }},
            last_cycle_time: 0,
            high_cycle_time: 0,
            low_cycle_time: 0
        };

        // Fetch Data from Server (Realtime)
        function fetchData() {
            console.log('Fetching data for monitoring ID:', monitoringId);
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/tv-data`,
                type: 'GET',
                success: function(data) {
                    console.log('✓ Realtime Data Received:', data);
                    console.log('OEE Metrics:', {
                        oee: data.oee,
                        availability: data.availability,
                        performance: data.performance,
                        quality: data.quality,
                        uptime: data.uptime
                    });
                    console.log('Cycle Times:', {
                        avg: data.avg_cycle_time,
                        last: data.last_cycle_time,
                        high: data.high_cycle_time,
                        low: data.low_cycle_time
                    });
                    // Update QTY with animation if changed
                    if (data.qty_actual !== previousData.qty_actual) {
                        animateValue('#actualQty', previousData.qty_actual, data.qty_actual);
                        previousData.qty_actual = data.qty_actual;
                    }

                    if (data.qty_ng !== previousData.qty_ng) {
                        animateValue('#ngQty', previousData.qty_ng, data.qty_ng);
                        previousData.qty_ng = data.qty_ng;
                    }

                    // Update Progress
                    const progress = (data.qty_ok / data.wo_qty * 100).toFixed(1);
                    $('#progressPercent').text(progress + '%');

                    // Update Status
                    updateStatus(data.current_status);

                    // Update OEE Metrics with smooth transition
                    updateMetricValue('#oee', data.oee, previousData.oee);
                    updateMetricValue('#availability', data.availability, previousData.availability);
                    updateMetricValue('#performance', data.performance, previousData.performance);
                    updateMetricValue('#quality', data.quality, previousData.quality);
                    updateMetricValue('#uptime', data.uptime, previousData.uptime);

                    // Update Cycle Times with animation
                    updateMetricValue('#avgCycleTime', data.avg_cycle_time, previousData.avg_cycle_time);
                    updateMetricValue('#lastCycleTime', data.last_cycle_time, previousData.last_cycle_time ||
                        0);
                    updateMetricValue('#highCycleTime', data.high_cycle_time, previousData.high_cycle_time ||
                        0);
                    updateMetricValue('#lowCycleTime', data.low_cycle_time, previousData.low_cycle_time || 0);

                    // Update Finish Time
                    updateFinishTime(data.qty_actual, data.wo_qty, data.avg_cycle_time);

                    // Update Timeline
                    if (data.timeline) {
                        updateTimeline(data.timeline);
                    }

                    // Store current values
                    previousData.oee = data.oee;
                    previousData.availability = data.availability;
                    previousData.performance = data.performance;
                    previousData.quality = data.quality;
                    previousData.uptime = data.uptime;
                    previousData.avg_cycle_time = data.avg_cycle_time;
                    previousData.last_cycle_time = data.last_cycle_time;
                    previousData.high_cycle_time = data.high_cycle_time;
                    previousData.low_cycle_time = data.low_cycle_time;
                },
                error: function(xhr, status, error) {
                    console.error('❌ Failed to fetch data');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                }
            });
        }

        // Animate value changes
        function animateValue(selector, start, end) {
            const element = $(selector);
            const duration = 500; // 500ms animation
            const steps = 20;
            const increment = (end - start) / steps;
            let current = start;
            let step = 0;

            const timer = setInterval(function() {
                step++;
                current += increment;
                if (step >= steps) {
                    element.text(Math.round(end));
                    clearInterval(timer);
                } else {
                    element.text(Math.round(current));
                }
            }, duration / steps);
        }

        // Update metric value with color change if improved
        function updateMetricValue(selector, newValue, oldValue) {
            const element = $(selector);
            const numNew = parseFloat(newValue) || 0;
            const numOld = parseFloat(oldValue) || 0;

            console.log(`Updating ${selector}: ${numOld} -> ${numNew}`);

            // Always update the text
            element.text(numNew);

            // Flash color if changed
            if (numNew > numOld) {
                // Improved - flash green
                element.css('color', '#4ade80');
                setTimeout(() => {
                    element.css('color', '#ffffff');
                }, 300);
            } else if (numNew < numOld) {
                // Decreased - flash red
                element.css('color', '#ef4444');
                setTimeout(() => {
                    element.css('color', '#ffffff');
                }, 300);
            }
        }

        // Initial finish time calculation
        updateFinishTime(
            {{ $monitoring->qty_actual }},
            {{ $monitoring->wo_qty }},
            {{ $monitoring->cycle_time }}
        );

        // Auto refresh every 2 seconds for realtime updates
        setInterval(fetchData, 2000);
        fetchData();

        // Modal Functions
        function openNgModal(qty = 1) {
            $('input[name="qty"]').val(qty);
            $('#ngModal').removeClass('hidden');
        }

        function closeNgModal() {
            $('#ngModal').addClass('hidden');
            $('#ngForm')[0].reset();
        }

        function openDowntimeModal() {
            $('#downtimeModal').removeClass('hidden');
        }

        function closeDowntimeModal() {
            $('#downtimeModal').addClass('hidden');
            $('#downtimeForm')[0].reset();
        }

        // NG Form Submit
        $('#ngForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/save-ng`,
                type: 'POST',
                data: $(this).serialize() + '&_token={{ csrf_token() }}',
                success: function(response) {
                    closeNgModal();
                    alert('NG recorded successfully');
                    fetchData();
                },
                error: function() {
                    alert('Error recording NG');
                }
            });
        });

        // Downtime Form Submit
        $('#downtimeForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/save-downtime`,
                type: 'POST',
                data: $(this).serialize() + '&_token={{ csrf_token() }}',
                success: function(response) {
                    closeDowntimeModal();
                    alert('Downtime recorded successfully');
                    fetchData();
                },
                error: function() {
                    alert('Error recording downtime');
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
                        openNgModal(response.qty);
                    }
                }
            });

            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/check-mqtt-downtime-signal`,
                type: 'GET',
                success: function(response) {
                    if (response.show) {
                        openDowntimeModal();
                    }
                }
            });

            $.ajax({
                url: `/production/production-monitoring/${monitoringId}/check-mqtt-status-signal`,
                type: 'GET',
                success: function(response) {
                    if (response.show && response.status) {
                        updateStatus(response.status);
                        fetchData();
                    }
                }
            });
        }, 2000);
    </script>
</body>

</html>
