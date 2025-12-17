// TV Display JavaScript - Production Monitoring
// Realtime updates, OEE metrics, and timeline visualization

// Configuration
const CONFIG = {
    refreshInterval: 1000, // 1 second (faster for timer sync)
    animationDuration: 500,
    timezone: "Asia/Jakarta",
};

// State Management
const state = {
    monitoringId: null,
    hasAutoSaved: false, // Prevents multiple saves
    currentStatus: 'Ready',
    statusStartTime: null, // For current status timer
    lastServerSync: 0,
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
        low_cycle_time: 0,
    },
};

// Initialize
function init(monitoringId, initialData) {
    state.monitoringId = monitoringId;
    state.previousData.qty_actual = initialData.qty_actual || 0;
    state.previousData.qty_ng = initialData.qty_ng || 0;
    state.previousData.qty_ok = initialData.qty_ok || 0;
    state.previousData.avg_cycle_time = initialData.cycle_time || 0;
    
    // Initialize Status Timer
    state.currentStatus = initialData.current_status || 'Ready';
    state.statusStartTime = Date.now(); // Assume start now, will correct with sync
    fetchCurrentStatus(); // Initial sync

    // Start timers
    updateClock();
    setInterval(updateClock, 1000);
    setInterval(updateStatusTimer, 1000);

    // Initial finish time calculation
    updateFinishTime(
        state.previousData.qty_actual,
        initialData.wo_qty || 0,
        state.previousData.avg_cycle_time
    );

    // Initial check for finish
    checkProductionFinish(state.previousData.qty_actual, initialData.wo_qty || 0);

    // Start data fetching
    fetchData();
    setInterval(fetchData, CONFIG.refreshInterval);

    // Start polling for MQTT signals (fallback/primary)
    setInterval(checkMqttSignals, 500); // Check every 0.5s

    // Setup form handlers
    setupFormHandlers();

    console.log('TV Display Initialized', state);
}

// Clock and Date (Indonesia Timezone - WIB UTC+7)
function updateClock() {
    const now = new Date();
    // const indonesiaTime = new Date(
    //     now.toLocaleString("en-US", {
    //         timeZone: CONFIG.timezone,
    //     })
    // );
    // Use local browser time for display to ensure smooth seconds, assuming PC is set correctly
    // or keep using timezone if strict
    
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

    $("#clock").text(timeString);
    $("#date").text(dateString);
}


// --- Status Timer Logic ---

function updateStatusTimer() {
    if (!state.statusStartTime) return;

    const now = Date.now();
    const diff = Math.floor((now - state.statusStartTime) / 1000);
    const seconds = Math.max(0, diff);

    const timerString = formatDurationHHMMSS(seconds);
    console.log("Updating timer:", timerString);
    $('#currentTimer').text(timerString);
}

function formatDurationHHMMSS(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const secs = Math.floor(totalSeconds % 60);

    return String(hours).padStart(2, '0') + ':' +
           String(minutes).padStart(2, '0') + ':' +
           String(secs).padStart(2, '0');
}

// Sync status specifically to reset timer if changed
function fetchCurrentStatus() {
    if (!state.monitoringId) return;

    $.ajax({
        url: `/production/production-monitoring/${state.monitoringId}/get-current-status`,
        method: 'GET',
        success: function(data) {
            if (data.success) {
                const newStatus = data.current_status || 'Ready';
                if (state.currentStatus !== newStatus) {
                    console.log('Status changed (sync):', state.currentStatus, '->', newStatus);
                    updateTimerStatus(newStatus);
                }
            }
        },
        error: function(err) {
            console.error('Error syncing status:', err);
        }
    });
}

// Global function to update timer status (called by MQTT handler or internal sync)
window.updateTimerStatus = function(newStatus) {
    if (state.currentStatus !== newStatus) {
        state.currentStatus = newStatus;
        state.statusStartTime = Date.now();
        $('#currentTimer').text('00:00:00');
        updateStatus(newStatus); // Update visual badge
        console.log('Timer RESET for status:', newStatus);
    }
};

window.forceResetTimer = function() {
    state.statusStartTime = Date.now();
    $('#currentTimer').text('00:00:00');
    console.log('Timer FORCE RESET');
};


// --- Finish Logic ---

function checkProductionFinish(actualQty, targetQty) {
    // If we don't have args, try to read from DOM or state? 
    // Better to pass them in.
    if (actualQty === undefined) actualQty = state.previousData.qty_actual;
    // targetQty is usually static per session, but valid to read from API
    
    // Read target from DOM if not passed (fallback)
    if (targetQty === undefined) {
         const targetText = $('#targetQty').text();
         targetQty = parseInt(targetText) || 0;
    }

    const currentTimerLabel = $('#currentTimer').parent().find('div:first-child');
    const currentTimerContainer = $('#currentTimer').parent();

    if (actualQty >= targetQty && targetQty > 0) {
        // FINISH STATE
        if (currentTimerLabel.length) {
            currentTimerLabel.text('FINISH');
            currentTimerLabel.addClass('text-green-400').removeClass('text-white');
        }
        
        // Change background to Green
        currentTimerContainer.removeClass('from-blue-600 to-blue-700').addClass('from-green-600 to-green-700');

        // Stop timer visually? Or just let it run? 
        // User request: "Stop timer if still running" in blade logic.
        // But logic says "RunningStartTime = null". 
        // We'll leave the timer running as "Time since Finish" or stop it?
        // Let's just update the visual style for now.
    } else {
        // NORMAL STATE
        if (currentTimerLabel.length && currentTimerLabel.text() === 'FINISH') {
            currentTimerLabel.text('CURRENT TIME');
            currentTimerLabel.removeClass('text-green-400').addClass('text-white');
            currentTimerContainer.removeClass('from-green-600 to-green-700').addClass('from-blue-600 to-blue-700');
        }
    }
}


// --- Finish Time Estimation ---

// Calculate Estimated Finish Time 
function calculateFinishTime(qty_ok, targetQty, avgCycleTime) {
    if (qty_ok >= targetQty) {
        return "COMPLETED";
    }
    if (avgCycleTime <= 0) {
        return "Calculating...";
    }
    const remainingQty = targetQty - qty_ok;
    const remainingSeconds = remainingQty * avgCycleTime;
    const now = new Date();
    const finishTime = new Date(now.getTime() + remainingSeconds * 1000);

    const hours = String(finishTime.getHours()).padStart(2, "0");
    const minutes = String(finishTime.getMinutes()).padStart(2, "0");
    const timeStr = `${hours}:${minutes}`;

    const isSameDay = finishTime.getDate() === now.getDate();
    
    // Check if Tomorrow
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const isTomorrow = finishTime.getDate() === tomorrow.getDate();
    
    if (isSameDay) {
        return `Today, ${timeStr}`;
    }
    if (isTomorrow) {
        return `Tomorrow, ${timeStr}`;
    }

    // Else show date
    const day = String(finishTime.getDate()).padStart(2, '0');
    const month = String(finishTime.getMonth() + 1).padStart(2, '0');
    return `${day}/${month}, ${timeStr}`;
}

function updateFinishTime(qty_ok, targetQty, avgCycleTime) {
    const finishTime = calculateFinishTime(qty_ok, targetQty, avgCycleTime);
    $("#finishTime").text(finishTime); // Note: #finishTime might not exist in new layout?
}


// --- Visual Updates ---

// Update Machine Status Badge
function updateStatus(status) {
    const badge = $("#statusBadge");
    // Ensure case matching
    const s = (status || 'Ready').toLowerCase();

    // Reset Classes
    badge.attr('class', 'px-3 py-1.5 rounded-lg shadow-xl');

    let html = '';
    let bgClass = '';

    if (s === 'running') {
        bgClass = 'bg-gradient-to-r from-green-500 to-emerald-400';
        html = '<div class="text-lg font-black text-white tracking-widest">RUN</div>';
    } else if (s === 'ready' || s === 'idle') {
        bgClass = 'bg-gradient-to-r from-blue-500 to-blue-400';
        html = '<div class="text-lg font-black text-white tracking-widest">READY</div>';
    } else if (s === 'downtime') {
        bgClass = 'bg-gradient-to-r from-red-500 to-red-400';
        html = '<div class="text-lg font-black text-white tracking-widest">DOWN</div>';
    } else if (s === 'stop' || s === 'stopped') {
        bgClass = 'bg-gradient-to-r from-gray-500 to-gray-400';
        html = '<div class="text-lg font-black text-white tracking-widest">STOP</div>';
    } else {
        bgClass = 'bg-gradient-to-r from-gray-500 to-gray-400';
        html = `<div class="text-lg font-black text-white tracking-widest">${status.toUpperCase()}</div>`;
    }

    badge.addClass(bgClass);
    badge.html(html);
}


// Update Timeline Display
function updateTimeline(timeline) {
    const visualContainer = $("#timelineVisual");
    const labelsContainer = $("#timeLabels");
    
    if (!visualContainer.length || !timeline || timeline.length === 0) return;

    visualContainer.empty();
    // Only clear labels if we have new ones to show, otherwise keep old or clear? 
    // Best to clear and rebuild to match the visual.
    labelsContainer.empty();

    const totalDuration = timeline.reduce((sum, item) => sum + (item.duration || 0), 0);
    if (totalDuration === 0) return;

    // --- 1. Build Visual Bar ---
    timeline.forEach((item) => {
        const percentage = ((item.duration || 0) / totalDuration) * 100;
        let colorClass = 'bg-blue-500';

        switch(item.status) {
            case 'Running': colorClass = 'bg-green-500'; break;
            case 'Downtime': colorClass = 'bg-red-500'; break;
            case 'Ready': colorClass = 'bg-yellow-500'; break;
            case 'Stopped':
            case 'Stop': colorClass = 'bg-gray-500'; break;
        }

        // Fix: Use single backslash for newline in JS string
        const startTime = item.start_time || 'N/A';
        const endTime = item.end_time || 'Ongoing';
        const durationStr = formatDurationHHMMSS(item.duration || 0);
        const tooltipText = `${item.status}\nStart: ${startTime}\nEnd: ${endTime}\nDuration: ${durationStr}`;

        const segment = $('<div></div>')
            .addClass(colorClass)
            .css('width', percentage + '%')
            .attr('title', tooltipText);
        
        visualContainer.append(segment);
    });

    // --- 2. Build Time Labels ---
    // Start Time (from first element)
    const firstTimeStr = timeline[0].start_time;
    if (!firstTimeStr) return;

    // Parse Start Time (HH:mm:ss)
    const [h, m, s] = firstTimeStr.split(':').map(Number);
    const startDate = new Date();
    startDate.setHours(h, m, s, 0);

    // Generate 6 labels (0%, 20%, 40%, 60%, 80%, 100%)
    const labelCount = 6;
    
    for (let i = 0; i < labelCount; i++) {
        const pct = i / (labelCount - 1);
        const elapsedSeconds = totalDuration * pct;
        
        const labelDate = new Date(startDate.getTime() + (elapsedSeconds * 1000));
        
        const hh = String(labelDate.getHours()).padStart(2, '0');
        const mm = String(labelDate.getMinutes()).padStart(2, '0');
        const timeStr = `${hh}:${mm}`;

        const labelDiv = $('<div></div>').addClass('flex flex-col items-center');
        labelDiv.append('<div class="w-0.5 h-2 bg-slate-500 mb-1"></div>');
        labelDiv.append(`<span class="text-xs">${timeStr}</span>`);
        
        labelsContainer.append(labelDiv);
    }
}


// --- Data Fetching ---

function fetchData() {
    if (!state.monitoringId) return;

    $.ajax({
        url: `/production/production-monitoring/${state.monitoringId}/tv-data`,
        type: "GET",
        success: function (data) {
            // Update KPIs with animation
            updateKpiWithAnimation('#actualQty', data.qty_actual);
            updateKpiWithAnimation('#ngQty', data.qty_ng);
            updateKpiWithAnimation('#targetQty', data.wo_qty);

            // Calculate Progress
            const progress = data.wo_qty > 0 ? ((data.qty_ok / data.wo_qty) * 100).toFixed(1) : '0.0';
            updateKpiWithAnimation('#progressPercent', progress + '%', false); 
            // Note: progressPercent might be text, so false for 'isNumber' animation

            // Update Status (only if backend says so, but updateTimerStatus handles change)
            if (data.current_status && state.currentStatus !== data.current_status) {
                updateTimerStatus(data.current_status);
            }

            // OEE Metrics
            updateMetricValue('#oee', data.oee);
            updateMetricValue('#availability', data.availability);
            updateMetricValue('#performance', data.performance);
            updateMetricValue('#quality', data.quality);
            updateMetricValue('#uptime', data.uptime);

            // Cycle Times
            updateMetricValue('#avgCycleTime', data.avg_cycle_time);
            updateMetricValue('#lastCycleTime', data.last_cycle_time);
            updateMetricValue('#highCycleTime', data.high_cycle_time);
            updateMetricValue('#lowCycleTime', data.low_cycle_time);

            // Timeline
            if (data.timeline) {
                updateTimeline(data.timeline);
            }

            // Check Finish
            checkProductionFinish(data.qty_actual, data.wo_qty);

            // Auto Save
            if (data.qty_ok >= data.wo_qty && !state.hasAutoSaved && data.wo_qty > 0) {
                autoSaveTransaction();
                state.hasAutoSaved = true;
            }

            // Update State
            state.previousData = { ...state.previousData, ...data };
        },
        error: function(err) {
            console.error('Fetch data failed', err);
        }
    });
}

function updateKpiWithAnimation(selector, newValue, isNumber = true) {
    const el = $(selector);
    if (!el.length) return;
    
    const currentText = el.text();
    const newText = newValue.toString();

    if (currentText !== newText) {
        el.addClass('animate-pulse');
        el.text(newText);
        setTimeout(() => el.removeClass('animate-pulse'), 1000);
    }
}

function updateMetricValue(selector, value) {
    const el = $(selector);
    if (!el.length) return;

    if (selector === '#oee') {
        updateOeeGauge(value);
    }
    
    // Percentage handling
    const isPercent = ['#oee', '#availability', '#performance', '#quality', '#uptime'].includes(selector);
    const num = parseFloat(value) || 0;
    
    if (isPercent) {
        el.text(num.toFixed(1) + '%');
    } else {
        // Integer check
        el.text(Number.isInteger(num) ? num : num.toFixed(1));
    }
}

function updateOeeGauge(value) {
    const percentage = Math.min(Math.max(parseFloat(value) || 0, 0), 100);
    const needle = $('#oee_needle');
    
    if (needle.length) {
        // -90deg (0%) to 90deg (100%)
        const rotation = (percentage * 1.8) - 90;
        needle.css('transform', `rotate(${rotation}deg)`);
    }
}

function autoSaveTransaction() {
    console.log("Auto-saving transaction...");
    $.ajax({
        url: '/production/wo-transaction', 
        type: 'POST',
        data: {
            monitoring_id: state.monitoringId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(res) { console.log("Auto-saved:", res); },
        error: function(err) { console.error("Auto-save error", err); }
    });
}


// --- MQTT Signals & Modals ---

function checkMqttSignals() {
    if (!state.monitoringId) return;

    // Check NG Signal
    $.get(`/production/production-monitoring/${state.monitoringId}/check-mqtt-ng-signal`, function(data) {
        if (data.show && data.ng_type) {
            console.log('MQTT NG Signal:', data);
            openNgModal(data);
        }
        // Also update counts if provided
        if (data.qty_ng != null) updateKpiWithAnimation('#ngQty', data.qty_ng);
        if (data.qty_actual != null) updateKpiWithAnimation('#actualQty', data.qty_actual);
    });

    // Check Downtime Signal
    $.get(`/production/production-monitoring/${state.monitoringId}/check-mqtt-downtime-signal`, function(data) {
        if (data.show) {
            console.log('MQTT Downtime Signal:', data);
            openDowntimeModal(data);
        }
    });

    // Check Status Signal
    $.get(`/production/production-monitoring/${state.monitoringId}/check-mqtt-status-signal`, function(data) {
        if (data.show && data.status) {
            console.log('MQTT Status Signal:', data);
            updateTimerStatus(data.status);
        }
    });
}

function openNgModal(data = {}) {
    // data can be just qty (old style) or full object (MQTT)
    const qty = typeof data === 'object' ? (data.qty || 1) : data;
    const modal = $('#ngModal');
    const form = $('#ngForm');
    
    if (!modal.length || !form.length) return;

    // Reset first
    form[0].reset();
    
    // Fill data
    form.find('[name="qty"]').val(qty);
    
    if (data.ng_type) form.find('[name="ng_type"]').val(data.ng_type);
    if (data.ng_reason) form.find('[name="ng_reason"]').val(data.ng_reason);
    
    // Handle Auto-saved state
    const submitBtn = form.find('button[type="submit"]');
    const title = modal.find('h2');

    if (data.auto_saved) {
        form.find('[name="notes"]').val('Auto-saved from MQTT signal - Review and confirm');
        title.text('NG Record (Auto-Saved)').addClass('text-green-400');
        submitBtn.text('Confirm & Close')
                 .removeClass('bg-red-600 hover:bg-red-700')
                 .addClass('bg-green-600 hover:bg-green-700');
    } else {
        // Reset styles
        title.text('Record NG').removeClass('text-green-400');
        submitBtn.text('Save NG')
                 .addClass('bg-red-600 hover:bg-red-700')
                 .removeClass('bg-green-600 hover:bg-green-700');
        
        if (data.ng_type) {
            form.find('[name="notes"]').val('Auto-filled from MQTT signal');
        }
    }

    modal.removeClass('hidden');
}

function openDowntimeModal(data = {}) {
    const modal = $('#downtimeModal');
    const form = $('#downtimeForm');
    
    if (!modal.length || !form.length) return;

    form[0].reset();

    if (data.downtime_type) form.find('[name="downtime_type"]').val(data.downtime_type);
    if (data.downtime_reason) form.find('[name="downtime_reason"]').val(data.downtime_reason);

    // Handle Auto-saved state
    const submitBtn = form.find('button[type="submit"]');
    const title = modal.find('h2');

    if (data.auto_saved) {
        form.find('[name="notes"]').val('Auto-saved from MQTT signal - Review and confirm');
        title.text('Downtime Record (Auto-Saved)').addClass('text-green-400');
        submitBtn.text('Confirm & Close')
                 .removeClass('bg-yellow-600 hover:bg-yellow-700')
                 .addClass('bg-green-600 hover:bg-green-700');
    } else {
        title.text('Record Downtime').removeClass('text-green-400');
        submitBtn.text('Save Downtime')
                 .addClass('bg-yellow-600 hover:bg-yellow-700')
                 .removeClass('bg-green-600 hover:bg-green-700');

        if (data.downtime_type) {
             form.find('[name="notes"]').val('Auto-filled from MQTT signal');
        }
    }

    modal.removeClass('hidden');
}

function closeNgModal() {
    $('#ngModal').addClass('hidden');
}

function closeDowntimeModal() {
    $('#downtimeModal').addClass('hidden');
}

function setupFormHandlers() {
    // NG Form
    $('#ngForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.text().includes('Confirm')) {
            closeNgModal();
            return;
        }

        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(item => data[item.name] = item.value);

        $.ajax({
            url: `/production/production-monitoring/${state.monitoringId}/save-ng`,
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if(res.success) {
                    closeNgModal();
                    fetchData(); // Refresh immediate
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function() { alert('Failed to save NG'); }
        });
    });

    // Downtime Form
    // Downtime Form
    $('#downtimeForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        console.log('Downtime form submitted');

        const submitBtn = $(this).find('button[type="submit"]');
        if (submitBtn.text().includes('Confirm')) {
            console.log('Confirmed auto-save');
            closeDowntimeModal();
            return;
        }

        if (!state.monitoringId) {
            alert('Error: Monitoring ID not found');
            return;
        }

        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(item => data[item.name] = item.value);

        console.log('Saving downtime:', data);

        $.ajax({
            url: `/production/production-monitoring/${state.monitoringId}/save-downtime`,
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                console.log('Save response:', res);
                if(res.success) {
                    closeDowntimeModal();
                    fetchData();
                    // Reset form after save
                    $('#downtimeForm')[0].reset();
                } else {
                    alert('Error: ' + (res.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, err) { 
                console.error('Failed to save Downtime:', err, xhr.responseText);
                alert('Failed to save Downtime: ' + err); 
            }
        });
    });
}

// Global Exports
window.initTvDisplay = init;
window.openNgModal = openNgModal;
window.closeNgModal = closeNgModal;
window.openDowntimeModal = openDowntimeModal;
window.closeDowntimeModal = closeDowntimeModal;
window.checkProductionFinish = checkProductionFinish;
