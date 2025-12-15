// TV Display JavaScript - Production Monitoring
// Realtime updates, OEE metrics, and timeline visualization

// Configuration
const CONFIG = {
    refreshInterval: 2000, // 2 seconds
    animationDuration: 500,
    timezone: 'Asia/Jakarta'
};

// State Management
const state = {
    monitoringId: null,
    previousData: {
        qty_actual: 0,
        qty_ng: 0,
        qty_ok: 0,
        oee: 0,
        availability: 0,
        performance: 0,
        quality: 0,
        uptime: 0,
        avg_cycle_time: 0,
        last_cycle_time: 0,
        high_cycle_time: 0,
        low_cycle_time: 0
    }
};

// Initialize
function init(monitoringId, initialData) {
    state.monitoringId = monitoringId;
    state.previousData.qty_actual = initialData.qty_actual;
    state.previousData.qty_ng = initialData.qty_ng;
    state.previousData.qty_ok = initialData.qty_ok;
    state.previousData.avg_cycle_time = initialData.cycle_time;

    // Start clock
    updateClock();
    setInterval(updateClock, 1000);

    // Initial finish time calculation
    updateFinishTime(initialData.qty_actual, initialData.wo_qty, initialData.cycle_time);

    // Start data fetching
    fetchData();
    setInterval(fetchData, CONFIG.refreshInterval);

    // Start MQTT signal checking
    setInterval(checkMqttSignals, CONFIG.refreshInterval);

    // Setup form handlers
    setupFormHandlers();
}

// Clock and Date (Indonesia Timezone - WIB UTC+7)
function updateClock() {
    const now = new Date();
    const indonesiaTime = new Date(now.toLocaleString('en-US', {
        timeZone: CONFIG.timezone
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

    const indonesiaFinishTime = new Date(finishTime.toLocaleString('en-US', {
        timeZone: CONFIG.timezone
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
        badge.html('<div class="text-2xl font-black text-white tracking-widest">IDLE</div>');
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
    if (!timeline || timeline.length === 0) {
        console.log('No timeline data');
        return;
    }

    console.log('=== TIMELINE DEBUG ===');
    console.log('Timeline data received:', JSON.stringify(timeline, null, 2));

    // Parse all timeline items and calculate time range
    let timelineItems = [];
    let minTime = null;
    let maxTime = null;

    timeline.forEach((item) => {
        // Parse start_time (format: "HH:mm:ss" dari server)
        const startParts = item.start_time.split(':');
        const startHours = parseInt(startParts[0]);
        const startMinutes = parseInt(startParts[1]);
        const startSeconds = parseInt(startParts[2]) || 0;
        const startTotalSeconds = startHours * 3600 + startMinutes * 60 + startSeconds;

        // Parse end_time jika ada
        let endTotalSeconds = startTotalSeconds;
        if (item.end_time) {
            const endParts = item.end_time.split(':');
            const endHours = parseInt(endParts[0]);
            const endMinutes = parseInt(endParts[1]);
            const endSecs = parseInt(endParts[2]) || 0;
            endTotalSeconds = endHours * 3600 + endMinutes * 60 + endSecs;
        } else {
            // Fallback: gunakan duration jika end_time tidak ada
            endTotalSeconds = startTotalSeconds + (item.duration || 0);
        }

        const duration = endTotalSeconds - startTotalSeconds;

        if (minTime === null || startTotalSeconds < minTime) {
            minTime = startTotalSeconds;
        }
        if (maxTime === null || endTotalSeconds > maxTime) {
            maxTime = endTotalSeconds;
        }

        timelineItems.push({
            status: item.status,
            startSeconds: startTotalSeconds,
            endSeconds: endTotalSeconds,
            duration: duration,
            startTime: item.start_time,
            endTime: item.end_time
        });

        console.log(`Parsed: ${item.status} | Start: ${item.start_time} (${startTotalSeconds}s) | End: ${item.end_time || 'N/A'} (${endTotalSeconds}s) | Duration: ${duration}s`);
    });

    // Calculate total time range
    const totalRangeSeconds = maxTime - minTime;
    const displayRangeSeconds = Math.max(totalRangeSeconds, 3600); // minimum 1 hour

    console.log('Time range:', {
        minTime: formatTimeFromSeconds(minTime),
        maxTime: formatTimeFromSeconds(maxTime),
        totalRange: totalRangeSeconds,
        displayRange: displayRangeSeconds
    });

    // Build visual bars
    let visualHtml = '';

    timelineItems.forEach((item) => {
        let colorClass = '';
        let statusLabel = '';

        if (item.status === 'Running') {
            colorClass = 'bg-green-500';
            statusLabel = 'Running';
        } else if (item.status === 'Ready') {
            colorClass = 'bg-yellow-500';
            statusLabel = 'Ready';
        } else if (item.status === 'Downtime') {
            colorClass = 'bg-red-500';
            statusLabel = 'Downtime';
        } else if (item.status === 'Stop') {
            colorClass = 'bg-gray-500';
            statusLabel = 'Stop';
        } else {
            colorClass = 'bg-slate-500';
            statusLabel = item.status;
        }

        // Calculate width percentage based on duration
        const widthPercent = (item.duration / displayRangeSeconds) * 100;
        const durationFormatted = formatTime(item.duration);
        const endTimeDisplay = item.endTime || formatTimeFromSeconds(item.endSeconds);

        console.log(`${statusLabel}: ${item.startTime} - ${endTimeDisplay} (${durationFormatted}) = ${widthPercent.toFixed(2)}%`);

        // Clean design - only color bars with hover tooltip
        visualHtml += `
            <div class="${colorClass} transition-all duration-300 hover:opacity-80 cursor-pointer" 
                style="width: ${Math.max(widthPercent, 0.5)}%" 
                title="${statusLabel}&#10;Start: ${item.startTime}&#10;End: ${endTimeDisplay}&#10;Duration: ${durationFormatted}">
            </div>
        `;
    });

    $('#timelineVisual').html(visualHtml);

    // Update time labels based on actual timeline range
    if (minTime !== null) {
        const startH = Math.floor(minTime / 3600);
        const startM = Math.floor((minTime % 3600) / 60);
        
        // Calculate interval for 6 labels (5 intervals)
        const timeSpanMinutes = Math.ceil(displayRangeSeconds / 60);
        const intervalMinutes = Math.ceil(timeSpanMinutes / 5);

        let currentMinutes = startH * 60 + startM;

        let timeLabelsHtml = '';
        for (let i = 0; i < 6; i++) {
            const h = Math.floor(currentMinutes / 60) % 24;
            const m = currentMinutes % 60;
            const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;

            timeLabelsHtml += `
                <div class="flex flex-col items-center">
                    <div class="w-0.5 h-2 bg-slate-500 mb-1"></div>
                    <span>${timeStr}</span>
                </div>
            `;

            currentMinutes += intervalMinutes;
        }
        $('#timeLabels').html(timeLabelsHtml);
    }
}

// Helper function to format seconds to HH:MM:SS
function formatTimeFromSeconds(seconds) {
    const h = Math.floor(seconds / 3600) % 24;
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

// Fetch Data from Server (Realtime)
function fetchData() {
    console.log('Fetching data for monitoring ID:', state.monitoringId);
    $.ajax({
        url: `/production/production-monitoring/${state.monitoringId}/tv-data`,
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
            if (data.qty_actual !== state.previousData.qty_actual) {
                animateValue('#actualQty', state.previousData.qty_actual, data.qty_actual);
                state.previousData.qty_actual = data.qty_actual;
            }

            if (data.qty_ng !== state.previousData.qty_ng) {
                animateValue('#ngQty', state.previousData.qty_ng, data.qty_ng);
                state.previousData.qty_ng = data.qty_ng;
            }

            // Update Progress
            const progress = (data.qty_ok / data.wo_qty * 100).toFixed(1);
            $('#progressPercent').text(progress + '%');

            // Update Status
            updateStatus(data.current_status);

            // Update OEE Metrics with smooth transition
            updateMetricValue('#oee', data.oee, state.previousData.oee);
            updateMetricValue('#availability', data.availability, state.previousData.availability);
            updateMetricValue('#performance', data.performance, state.previousData.performance);
            updateMetricValue('#quality', data.quality, state.previousData.quality);
            updateMetricValue('#uptime', data.uptime, state.previousData.uptime);

            // Update Cycle Times with animation
            updateMetricValue('#avgCycleTime', data.avg_cycle_time, state.previousData.avg_cycle_time);
            updateMetricValue('#lastCycleTime', data.last_cycle_time, state.previousData.last_cycle_time || 0);
            updateMetricValue('#highCycleTime', data.high_cycle_time, state.previousData.high_cycle_time || 0);
            updateMetricValue('#lowCycleTime', data.low_cycle_time, state.previousData.low_cycle_time || 0);

            // Update Finish Time
            updateFinishTime(data.qty_actual, data.wo_qty, data.avg_cycle_time);

            // Update Timeline
            if (data.timeline) {
                updateTimeline(data.timeline);
            }

            // Store current values
            state.previousData.oee = data.oee;
            state.previousData.availability = data.availability;
            state.previousData.performance = data.performance;
            state.previousData.quality = data.quality;
            state.previousData.uptime = data.uptime;
            state.previousData.avg_cycle_time = data.avg_cycle_time;
            state.previousData.last_cycle_time = data.last_cycle_time;
            state.previousData.high_cycle_time = data.high_cycle_time;
            state.previousData.low_cycle_time = data.low_cycle_time;
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
    }, CONFIG.animationDuration / steps);
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

// Setup Form Handlers
function setupFormHandlers() {
    // NG Form Submit
    $('#ngForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: `/production/production-monitoring/${state.monitoringId}/save-ng`,
            type: 'POST',
            data: $(this).serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
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
            url: `/production/production-monitoring/${state.monitoringId}/save-downtime`,
            type: 'POST',
            data: $(this).serialize() + '&_token=' + $('meta[name="csrf-token"]').attr('content'),
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
}

// Check for MQTT signals
function checkMqttSignals() {
    // Check NG signal
    $.ajax({
        url: `/production/production-monitoring/${state.monitoringId}/check-mqtt-ng-signal`,
        type: 'GET',
        success: function(response) {
            if (response.show && response.qty) {
                openNgModal(response.qty);
            }
        }
    });

    // Check Downtime signal
    $.ajax({
        url: `/production/production-monitoring/${state.monitoringId}/check-mqtt-downtime-signal`,
        type: 'GET',
        success: function(response) {
            if (response.show) {
                openDowntimeModal();
            }
        }
    });

    // Check Status signal
    $.ajax({
        url: `/production/production-monitoring/${state.monitoringId}/check-mqtt-status-signal`,
        type: 'GET',
        success: function(response) {
            if (response.show && response.status) {
                updateStatus(response.status);
                fetchData();
            }
        }
    });
}

// Make functions globally accessible for inline handlers
window.openNgModal = openNgModal;
window.closeNgModal = closeNgModal;
window.openDowntimeModal = openDowntimeModal;
window.closeDowntimeModal = closeDowntimeModal;
window.initTvDisplay = init;
