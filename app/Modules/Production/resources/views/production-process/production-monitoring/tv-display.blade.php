<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Production Monitoring TV Display</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>
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
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar */
        .scrollbar-custom::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .scrollbar-custom::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.3);
            border-radius: 3px;
        }

        .scrollbar-custom::-webkit-scrollbar-thumb {
            background: rgba(6, 182, 212, 0.5);
            border-radius: 3px;
        }

        .scrollbar-custom::-webkit-scrollbar-thumb:hover {
            background: rgba(6, 182, 212, 0.7);
        }

        /* Gauge Chart Styles */
        .gauge-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .gauge-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        @keyframes fillGauge {
            from {
                stroke-dashoffset: 251.2;
            }
        }

        /* Timeline adjustments */
        .timeline-container {
            min-height: 140px;
            display: flex;
            flex-direction: column;
        }

        .timeline-content {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .timeline-legend {
            flex-shrink: 0;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white h-screen overflow-hidden">

    <!-- Header Bar - Fixed Height -->
    <div class="bg-slate-800/90 backdrop-blur-sm border-b border-cyan-500/30 px-4 py-2 shadow-xl h-10 flex items-center">
        <div class="w-full grid grid-cols-3 items-center">
            <!-- Left: Current Info -->
            <div class="text-lg font-bold text-cyan-400 tracking-wide justify-self-start">CURRENT INFO</div>

            <!-- Center: Monitoring -->
            <div class="text-lg font-bold text-purple-400 tracking-wide text-center justify-self-center">
                MONITORING
            </div>

            <!-- Right: Clock -->
            <div class="justify-self-end">
                <div class="bg-slate-700/50 px-4 py-1.5 rounded-xl flex items-center gap-3">
                    <div id="clock" class="text-lg font-bold text-cyan-400 tracking-wider font-mono">00:00:00</div>
                    <div id="date" class="text-base text-slate-400 font-medium">14 Dec 2025</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="h-[calc(100vh-40px)] p-2 flex gap-2 overflow-hidden">

        <!-- LEFT PANEL -->
        <div class="w-[28%] flex flex-col gap-2 min-h-0">
            <!-- Production Info Card -->
            <div
                class="flex-1 bg-gradient-to-br from-slate-800 to-slate-900 rounded-lg shadow-2xl border border-slate-700/50 p-3 overflow-y-auto scrollbar-custom">
                <div class="space-y-1">
                    @php
                        $infoItems = [
                            'Lot' => $monitoring->workOrder->lot->lot_no ?? '-',
                            'Work Order' => $monitoring->wo_no,
                            'Lot Quantity' => $monitoring->wo_qty,
                            'Previous Qty' => '0',
                            'Machine' => $monitoring->machine->machine_name ?? '-',
                            'Operator' => $monitoring->operator,
                            'Supervisor' => $monitoring->supervisor,
                            'Shift' => $monitoring->shift->shift_name ?? '-',
                            'Item Number' => $monitoring->workOrder->part_no ?? '-',
                            'Item Description' => $monitoring->workOrder->part_name ?? '-',
                        ];
                    @endphp

                    @foreach ($infoItems as $label => $value)
                        <div
                            class="flex justify-between py-1.5 border-b border-slate-700/50 hover:bg-slate-700/30 px-2 rounded transition-all">
                            <span
                                class="text-slate-400 font-medium text-sm uppercase truncate mr-2">{{ $label }}</span>
                            <span class="font-bold text-white text-sm uppercase truncate text-right min-w-0"
                                id="{{ str_replace(' ', '', strtolower($label)) }}">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Bottom Cards Row -->
            <div class="flex gap-2 h-28">
                 <!-- Machine Status -->
                <div
                    class="flex-1 bg-gradient-to-br from-slate-800 to-slate-900 rounded-lg shadow-2xl border border-slate-700/50 p-2 flex flex-col justify-center">
                    <div class="text-base font-bold text-cyan-400 tracking-wide mb-1 text-center">STATUS</div>
                    <div class="flex items-center justify-center">
                        <div id="statusBadge"
                            class="bg-gradient-to-r from-green-500 to-emerald-400 px-3 py-1.5 rounded-lg shadow-xl">
                            <div class="text-lg font-black text-white tracking-widest">RUN</div>
                        </div>
                    </div>
                </div>
                <!-- Estimated Finish -->
                <div
                    class="flex-1 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg shadow-2xl p-2 flex flex-col justify-center">
                    <div class="text-base font-bold text-white tracking-wide mb-1 text-center">EST. FINISH</div>
                    <div id="finishTime" class="text-xl font-bold text-white text-center font-mono leading-none">Calculating...</div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="flex-1 flex flex-col gap-2 min-h-0 overflow-hidden">
            <!-- KPI Cards Row -->
            <div class="grid grid-cols-4 gap-3 h-32">
    @php
        $kpiCards = [
            [
                'id' => 'targetQty',
                'title' => 'TARGET QTY',
                'value' => $monitoring->wo_qty,
                'unit' => 'PCS',
                'color' => 'blue',
                'border' => 'border-blue-500',
                'text' => 'text-blue-300'  // Changed from text-blue-400 to text-blue-300
            ],
            [
                'id' => 'actualQty',
                'title' => 'ACTUAL QTY',
                'value' => $monitoring->qty_actual,
                'unit' => 'PCS',
                'color' => 'green',
                'border' => 'border-green-500',
                'text' => 'text-emerald-300'  // Changed to emerald-300 for brighter green
            ],
            [
                'id' => 'ngQty',
                'title' => 'NG QTY',
                'value' => $monitoring->qty_ng,
                'unit' => 'PCS',
                'color' => 'red',
                'border' => 'border-red-500',
                'text' => 'text-rose-300'  // Changed to rose-300 for brighter red
            ],
            [
                'id' => 'progressPercent',
                'title' => 'PROGRESS',
                'value' =>
                    $monitoring->wo_qty > 0
                        ? number_format(($monitoring->qty_ok / $monitoring->wo_qty) * 100, 1, '.', '') . '%'
                        : '0%',
                'unit' => '',
                'color' => 'amber',
                'border' => 'border-amber-500',
                'text' => 'text-amber-300'  // Changed from text-amber-400 to text-amber-300
            ],
        ];
    @endphp

    @foreach ($kpiCards as $card)
        <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-lg p-3 flex flex-col justify-between border-t-4 {{ $card['border'] }} relative overflow-hidden group hover:from-slate-700 hover:to-slate-700/80 transition-all duration-300">
            <div class="flex justify-between items-start">
                <div class="text-base text-slate-200 tracking-tighter shadow-black drop-shadow-lg font-mono uppercase">{{ $card['title'] }}</div>
                @if(!empty($card['unit']))
                    <div class="text-[15px] font-bold text-slate-400 bg-slate-800/50 px-2 py-0.5 rounded">{{ $card['unit'] }}</div>
                @endif
            </div>
            
            <div class="flex-1 flex items-end justify-end">
                <div id="{{ $card['id'] }}" class="text-5xl font-black {{ $card['text'] }} tracking-tighter drop-shadow-lg leading-none font-mono text-shadow-lg">
                    {{ $card['value'] }}
                </div>
            </div>
            
            <!-- Decorative background glow -->
            <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-{{ $card['color'] }}-500/10 rounded-full blur-2xl group-hover:bg-{{ $card['color'] }}-500/20 transition-all duration-500"></div>
        </div>
    @endforeach
</div>

            <!-- Cycle Time Section -->
            <div class="grid grid-cols-4 gap-3 h-40 mb-2">
                <!-- STD Cycle Time -->
                <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-lg p-3 flex flex-col justify-between border-l-4 border-cyan-500 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 p-2 opacity-5">
                        <i class="fas fa-clock text-6xl"></i>
                    </div>
                    <div class="text-lg font-bold text-cyan-300 tracking-widest uppercase mb-1">STD CYCLE TIME</div>
                    <div class="flex-1 flex flex-col items-center justify-center">
                        <div class="flex items-baseline gap-1">
                            <div id="stdCycleTime" class="text-5xl font-black text-white tracking-tighter shadow-black drop-shadow-lg font-mono">
                                {{ $monitoring->cycle_time }}
                            </div>
                            <span class="text-xl font-semibold text-slate-400">s</span>
                        </div>
                    </div>
                </div>

                <!-- Actual Cycle Time Section -->
                <div class="col-span-3 bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl shadow-lg p-3 flex flex-col relative overflow-hidden">
                    <div class="flex items-center gap-2 mb-2 border-b border-slate-600/50 pb-2">
                        <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
                        <div class="text-lg font-bold text-slate-300 tracking-widest uppercase">ACTUAL CYCLE TIME</div>
                    </div>
                    
                    <div class="flex-1 grid grid-cols-4 gap-0 divide-x divide-slate-600/50">
                        @php
                            $cycleItems = [
                                ['id' => 'avgCycleTime', 'label' => 'AVERAGE', 'color' => 'text-emerald-400'],
                                ['id' => 'lastCycleTime', 'label' => 'LAST', 'color' => 'text-blue-400'],
                                ['id' => 'highCycleTime', 'label' => 'HIGHEST', 'color' => 'text-amber-400'],
                                ['id' => 'lowCycleTime', 'label' => 'LOWEST', 'color' => 'text-indigo-400'],
                            ];
                        @endphp

                        @foreach ($cycleItems as $item)
                            <div class="flex flex-col items-center justify-center px-4 relative group hover:bg-white/5 transition-colors duration-300 rounded-lg">
                                <div class="text-lg font-bold text-slate-400 mb-1 tracking-widest uppercase">{{ $item['label'] }}</div>
                                <div class="flex items-baseline gap-1">
                                    <div id="{{ $item['id'] }}" class="text-4xl font-black {{ $item['color'] }} tracking-tight font-mono">0</div>
                                    <span class="text-xl font-medium text-slate-500">s</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- OEE Section with Gauge Charts -->
            <div class="grid grid-cols-6 gap-2 h-40">
    <!-- OEE Main Gauge (Now col-span-2 for smaller width) -->
    <div class="col-span-2 bg-gradient-to-br from-slate-700 to-slate-800 rounded-lg shadow-xl p-2 relative border border-slate-600/30 overflow-hidden">
        <div class="absolute top-2 left-3 text-lg font-bold text-purple-200 tracking-wider">OEE</div>
        
        <div class="w-full h-full flex items-end justify-center pb-1">
            <svg viewBox="0 0 200 150" class="w-full h-full" style="max-height: 150px;">
                <!-- Gauge Zones (Widened - Radius 85) -->
                <!-- Red Zone (0-50%) -->
                <path d="M 15 100 A 85 85 0 0 1 100 15" fill="none" stroke="#ef4444" stroke-width="15" />
                <!-- Yellow Zone (50-75%) -->
                <path d="M 100 15 A 85 85 0 0 1 160 40" fill="none" stroke="#eab308" stroke-width="15" />
                <!-- Green Zone (75-100%) -->
                <path d="M 160 40 A 85 85 0 0 1 185 100" fill="none" stroke="#10b981" stroke-width="15" />

                <!-- Ticks/Labels (Adjusted for wider gauge) -->
                <text x="15" y="115" fill="#94a3b8" font-size="12" text-anchor="middle">0%</text>
                <text x="100" y="10" fill="#94a3b8" font-size="12" text-anchor="middle">50%</text>
                <text x="170" y="30" fill="#94a3b8" font-size="12" text-anchor="middle">75%</text>
                <text x="185" y="115" fill="#94a3b8" font-size="12" text-anchor="middle">100%</text>

                <!-- Percentage Value -->
                <text x="100" y="140" text-anchor="middle" fill="white" font-size="40" font-weight="900" id="oee" font-family="'JetBrains Mono', monospace">0.0%</text>

                <!-- Needle -->
                <g id="oee_needle" style="transform-origin: 100px 100px; transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1); transform: rotate(-90deg);">
    <!-- White needle with subtle outline -->
    <path d="M 100 25 L 96 100 L 100 105 L 104 100 Z" fill="#ffffff" stroke="#e2e8f0" stroke-width="0.8" filter="drop-shadow(0 3px 4px rgba(0,0,0,0.4))"/>
    
    <!-- Center pivot circle -->
    <circle cx="100" cy="100" r="6" fill="#334155" stroke="#ffffff" stroke-width="2" />
</g>
            </svg>
        </div>
    </div>

    <!-- Small Cards for Breakdown - Now have more space (col-span-1 each) -->
    @php
        $smallCards = [
            ['id' => 'availability', 'title' => 'AVAILABILITY', 'color' => 'blue'],
            ['id' => 'performance', 'title' => 'PERFORMANCE', 'color' => 'green'],
            ['id' => 'quality', 'title' => 'QUALITY', 'color' => 'yellow'],
            ['id' => 'uptime', 'title' => 'UPTIME', 'color' => 'purple'],
        ];
    @endphp

    @foreach ($smallCards as $card)
        <div class="col-span-1 bg-gradient-to-br from-slate-700 to-slate-800 rounded-lg shadow-xl p-2 flex flex-col items-center justify-center border border-slate-600/30">
            <div class="text-[15px] font-bold text-slate-400 mb-1 tracking-wider text-center w-full uppercase">
                {{ $card['title'] }}
            </div>
            <div id="{{ $card['id'] }}" class="text-xl md:text-2xl font-black text-{{ $card['color'] }}-400 mb-0.5 leading-none font-mono">
                0.0%
            </div>
        </div>
    @endforeach
</div>

            <!-- Timeline Chart -->
            <div class="timeline-container p-1 flex flex-col min-h-0">
                <div class="text-base font-bold text-indigo-400 tracking-wide mb-2 truncate">
                    MACHINE STATUS TIMELINE
                </div>

                <div class="timeline-content">
                    <div class="flex-1 flex flex-col gap-2">
                        <div
                            class="relative h-8 bg-slate-700/30 rounded-lg border border-slate-600/50 overflow-hidden shadow-inner">
                            <div id="timelineVisual" class="absolute inset-0 flex">
                                <div class="bg-green-500" style="width: 40%"></div>
                                <div class="bg-red-500" style="width: 15%"></div>
                                <div class="bg-green-500" style="width: 30%"></div>
                                <div class="bg-yellow-500" style="width: 15%"></div>
                            </div>
                        </div>

                        <!-- Time Labels -->
                        <div class="relative">
                            <div id="timeLabels"
                                class="flex justify-between text-xs text-slate-300 font-semibold px-1">
                                @php
                                    $timeLabels = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00'];
                                @endphp
                                @foreach ($timeLabels as $time)
                                    <div class="flex flex-col items-center">
                                        <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                                        <span class="text-xs">{{ $time }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="timeline-legend flex gap-4 justify-center pt-3 ">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded shadow"></div>
                            <span class="text-sm text-slate-300 font-medium">Run</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded shadow"></div>
                            <span class="text-sm text-slate-300 font-medium">Down</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-gray-500 rounded shadow"></div>
                            <span class="text-sm text-slate-300 font-medium">Stop</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded shadow"></div>
                            <span class="text-sm text-slate-300 font-medium">Ready</span>
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
            class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-lg shadow-2xl border border-slate-700 p-4 w-80 max-w-[90vw]">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                <h2 class="text-lg font-bold text-white">Record NG</h2>
            </div>
            <form id="ngForm" class="space-y-3">
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-1">NG Type</label>
                    <select name="ng_type" required
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-red-500 focus:outline-none text-sm">
                        <option value="">-- Select Type --</option>
                        <option value="Material">Material</option>
                        <option value="Process">Process</option>
                        <option value="Machine">Machine</option>
                        <option value="Human Error">Human Error</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-1">NG Reason</label>
                    <input type="text" name="ng_reason" required
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-red-500 focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-1">Quantity</label>
                    <input type="number" name="qty" min="1" value="1" required
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-red-500 focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-red-500 focus:outline-none text-sm"></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeNgModal()"
                        class="flex-1 bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 rounded transition-colors text-sm">Cancel</button>
                    <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded transition-colors text-sm">Save
                        NG</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Downtime Modal -->
    <div id="downtimeModal"
        class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
        <div
            class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-lg shadow-2xl border border-slate-700 p-4 w-80 max-w-[90vw]">
            <div class="flex items-center gap-2 mb-4">
                <i class="fas fa-tools text-yellow-500 text-xl"></i>
                <h2 class="text-lg font-bold text-white">Record Downtime</h2>
            </div>
            <form id="downtimeForm" class="space-y-3">
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-1">Downtime Type</label>
                    <select name="downtime_type" required
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-yellow-500 focus:outline-none text-sm">
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
                    <label class="block text-slate-300 text-sm font-semibold mb-1">Downtime Reason</label>
                    <input type="text" name="downtime_reason" required
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-yellow-500 focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-semibold mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full bg-slate-700 text-white rounded px-3 py-2 border border-slate-600 focus:border-yellow-500 focus:outline-none text-sm"></textarea>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeDowntimeModal()"
                        class="flex-1 bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 rounded transition-colors text-sm">Cancel</button>
                    <button type="submit"
                        class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 rounded transition-colors text-sm">Save
                        Downtime</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="{{ asset('js/tv-display.js') }}?v={{ time() }}"></script>
    <script>
        // Initialize TV Display with monitoring data
        $(document).ready(function() {
            if (typeof window.initTvDisplay === 'function') {
                window.initTvDisplay({{ $monitoring->monitoring_id }}, {
                    qty_actual: {{ $monitoring->qty_actual }},
                    qty_ng: {{ $monitoring->qty_ng }},
                    qty_ok: {{ $monitoring->qty_ok }},
                    wo_qty: {{ $monitoring->wo_qty }},
                    cycle_time: {{ $monitoring->cycle_time }}
                });
            }

            // Update clock every second
            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                const dateString = now.toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });

                document.getElementById('clock').textContent = timeString;
                document.getElementById('date').textContent = dateString;
            }

            setInterval(updateClock, 1000);
            updateClock();

            // Adjust layout for timeline
            function adjustTimelineLayout() {
                const timelineContainer = document.querySelector('.timeline-container');
                const timelineContent = document.querySelector('.timeline-content');

                if (timelineContainer && timelineContent) {
                    const containerHeight = timelineContainer.offsetHeight;
                    const contentHeight = timelineContent.scrollHeight;

                    if (contentHeight > containerHeight) {
                        // Reduce legend font size
                        document.querySelectorAll('.timeline-legend .text-sm').forEach(el => {
                            el.classList.remove('text-sm');
                            el.classList.add('text-xs');
                        });

                        // Reduce timeline height
                        const timelineBar = document.querySelector('.timeline-content .h-8');
                        if (timelineBar) {
                            timelineBar.classList.remove('h-8');
                            timelineBar.classList.add('h-6');
                        }
                    }
                }
            }

            // Adjust layout on resize
            window.addEventListener('resize', adjustTimelineLayout);
            setTimeout(adjustTimelineLayout, 100);

            // Ensure timeline has minimum height
            document.querySelector('.timeline-container').style.minHeight = '140px';
        });
    </script>
</body>

</html>
