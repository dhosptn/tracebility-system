/**
 * MQTT Production Monitoring Handler
 * Handles real-time updates from MQTT signals
 */

class MqttProductionHandler {
  constructor(monitoringId) {
    this.monitoringId = monitoringId;
    this.pollingInterval = 2000; // 2 seconds
    this.intervalId = null;
    this.isPolling = false;
    this.lastStatus = null; // Track last status for change detection
  }

  /**
   * Start polling for MQTT updates
   */
  start() {
    if (this.isPolling) {
      console.warn('MQTT handler already running');
      return;
    }

    console.log(`Starting MQTT handler for monitoring ID: ${this.monitoringId}`);
    this.isPolling = true;
    this.poll();
    this.intervalId = setInterval(() => this.poll(), this.pollingInterval);
  }

  /**
   * Stop polling
   */
  stop() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
    this.isPolling = false;
    console.log('MQTT handler stopped');
  }

  /**
   * Poll for updates from server
   */
  async poll() {
    try {
      const response = await fetch(`/production/production-monitoring/${this.monitoringId}/tv-data`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      this.handleUpdate(data);
    } catch (error) {
      console.error('Error polling MQTT data:', error);
    }
  }

  /**
   * Handle data updates
   */
  handleUpdate(data) {
    // Update production quantities
    this.updateQuantities(data);

    // Update status
    this.updateStatus(data);

    // Update OEE metrics
    this.updateOEE(data);

    // Update cycle times
    this.updateCycleTimes(data);

    // Update timeline
    if (data.timeline) {
      this.updateTimeline(data.timeline);
    }

    // Handle MQTT signals
    if (data.mqtt_signals) {
      this.handleMqttSignals(data.mqtt_signals);
    }
  }

  /**
   * Update production quantities display
   */
  updateQuantities(data) {
    const elements = {
      wo_qty: document.getElementById('wo-qty'),
      qty_actual: document.getElementById('qty-actual'),
      qty_ok: document.getElementById('qty-ok'),
      qty_ng: document.getElementById('qty-ng')
    };

    if (elements.wo_qty) elements.wo_qty.textContent = data.wo_qty || 0;
    if (elements.qty_actual) elements.qty_actual.textContent = data.qty_actual || 0;
    if (elements.qty_ok) elements.qty_ok.textContent = data.qty_ok || 0;
    if (elements.qty_ng) elements.qty_ng.textContent = data.qty_ng || 0;

    // Update progress bar if exists
    const progressBar = document.getElementById('production-progress');
    if (progressBar && data.wo_qty > 0) {
      const percentage = (data.qty_actual / data.wo_qty * 100).toFixed(1);
      progressBar.style.width = `${percentage}%`;
      progressBar.textContent = `${percentage}%`;
    }
  }

  /**
   * Update status display
   */
  updateStatus(data) {
    const statusElement = document.getElementById('current-status');
    const status = data.current_status || 'Unknown';
    
    // Check if status changed or has final duration and trigger timer update
    if (this.lastStatus !== status || data.final_duration !== null) {
      console.log('Status update via MQTT:', status);
      
      if (typeof window.updateTimerStatus === 'function') {
        window.updateTimerStatus(status, data.current_status_start_time, data.final_duration);
      }
    }
    
    // Store current status for next comparison
    this.lastStatus = status;

    if (statusElement) {
      statusElement.textContent = status;

      // Update status badge color
      statusElement.className = 'badge';
      switch (status) {
        case 'Ready':
          statusElement.classList.add('badge-info');
          break;
        case 'Running':
          statusElement.classList.add('badge-success');
          break;
        case 'Downtime':
          statusElement.classList.add('badge-danger');
          break;
        case 'Stop':
          statusElement.classList.add('badge-secondary');
          break;
        default:
          statusElement.classList.add('badge-light');
      }
    }
  }

  /**
   * Update OEE metrics
   */
  updateOEE(data) {
    const metrics = {
      oee: document.getElementById('oee-value'),
      availability: document.getElementById('availability-value'),
      performance: document.getElementById('performance-value'),
      quality: document.getElementById('quality-value')
    };

    if (metrics.oee) metrics.oee.textContent = `${data.oee || 0}%`;
    if (metrics.availability) metrics.availability.textContent = `${data.availability || 0}%`;
    if (metrics.performance) metrics.performance.textContent = `${data.performance || 0}%`;
    if (metrics.quality) metrics.quality.textContent = `${data.quality || 0}%`;

    // Update progress circles if they exist
    this.updateProgressCircle('oee-circle', data.oee);
    this.updateProgressCircle('availability-circle', data.availability);
    this.updateProgressCircle('performance-circle', data.performance);
    this.updateProgressCircle('quality-circle', data.quality);
  }

  /**
   * Update circular progress indicator
   */
  updateProgressCircle(elementId, percentage) {
    const circle = document.getElementById(elementId);
    if (!circle) return;

    const value = parseFloat(percentage) || 0;
    const circumference = 2 * Math.PI * 45; // radius = 45
    const offset = circumference - (value / 100) * circumference;

    circle.style.strokeDasharray = `${circumference} ${circumference}`;
    circle.style.strokeDashoffset = offset;
  }

  /**
   * Update cycle time displays
   */
  updateCycleTimes(data) {
    const elements = {
      avg: document.getElementById('avg-cycle-time'),
      last: document.getElementById('last-cycle-time'),
      high: document.getElementById('high-cycle-time'),
      low: document.getElementById('low-cycle-time')
    };

    if (elements.avg) elements.avg.textContent = `${data.avg_cycle_time || 0}s`;
    if (elements.last) elements.last.textContent = `${data.last_cycle_time || 0}s`;
    if (elements.high) elements.high.textContent = `${data.high_cycle_time || 0}s`;
    if (elements.low) elements.low.textContent = `${data.low_cycle_time || 0}s`;
  }

  /**
   * Update timeline display
   */
  updateTimeline(timeline) {
    const timelineContainer = document.getElementById('status-timeline');
    if (!timelineContainer) return;

    timelineContainer.innerHTML = '';

    timeline.forEach(item => {
      const timelineItem = document.createElement('div');
      timelineItem.className = 'timeline-item';
      
      const statusClass = this.getStatusClass(item.status);
      const duration = item.duration > 0 ? `(${this.formatDuration(item.duration)})` : '';

      timelineItem.innerHTML = `
        <span class="timeline-time">${item.time}</span>
        <span class="timeline-status badge ${statusClass}">${item.status}</span>
        <span class="timeline-duration">${duration}</span>
      `;

      timelineContainer.appendChild(timelineItem);
    });
  }

  /**
   * Get CSS class for status
   */
  getStatusClass(status) {
    const statusMap = {
      'Ready': 'badge-info',
      'Running': 'badge-success',
      'Downtime': 'badge-danger',
      'Stop': 'badge-secondary'
    };
    return statusMap[status] || 'badge-light';
  }

  /**
   * Format duration in seconds to readable format
   */
  formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
      return `${hours}h ${minutes}m`;
    } else if (minutes > 0) {
      return `${minutes}m ${secs}s`;
    } else {
      return `${secs}s`;
    }
  }

  /**
   * Handle MQTT signals (show forms, etc.)
   */
  handleMqttSignals(signals) {
    // Show downtime form if signaled
    if (signals.show_downtime_form) {
      this.showDowntimeForm();
    }

    // Show NG form if signaled
    if (signals.show_ng_form) {
      this.showNgForm();
    }
  }

  /**
   * Show downtime form modal
   */
  showDowntimeForm() {
    const modal = document.getElementById('downtimeModal');
    if (modal) {
      // Using Bootstrap modal
      if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
      } else if (typeof $ !== 'undefined') {
        // jQuery fallback
        $(modal).modal('show');
      }
      console.log('Downtime form triggered by MQTT signal');
    }
  }

  /**
   * Show NG form modal
   */
  showNgForm() {
    const modal = document.getElementById('ngModal');
    if (modal) {
      // Using Bootstrap modal
      if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
      } else if (typeof $ !== 'undefined') {
        // jQuery fallback
        $(modal).modal('show');
      }
      console.log('NG form triggered by MQTT signal');
    }
  }

  /**
   * Manual trigger for QTY OK increment
   */
  async incrementQtyOk(qty = 1) {
    try {
      const response = await fetch(`/production/production-monitoring/${this.monitoringId}/update-qty-ok`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ qty })
      });

      if (!response.ok) {
        throw new Error('Failed to update QTY OK');
      }

      const data = await response.json();
      console.log('QTY OK updated:', data);
      
      // Immediately update display
      this.poll();
      
      return data;
    } catch (error) {
      console.error('Error updating QTY OK:', error);
      throw error;
    }
  }

  /**
   * Manual status change
   */
  async changeStatus(status) {
    try {
      const response = await fetch(`/production/production-monitoring/${this.monitoringId}/update-status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ status })
      });

      if (!response.ok) {
        throw new Error('Failed to update status');
      }

      const data = await response.json();
      console.log('Status updated:', data);
      
      // IMMEDIATELY reset timer for new status
      if (typeof window.updateTimerStatus === 'function') {
        window.updateTimerStatus(status, data.current_status_start_time, data.final_duration);
      }
      
      // Also force reset timer if function exists and not finished
      if (status.toLowerCase() !== 'finish' && typeof window.forceResetTimer === 'function') {
        window.forceResetTimer();
      }
      
      // Immediately update display
      this.poll();
      
      return data;
    } catch (error) {
      console.error('Error updating status:', error);
      throw error;
    }
  }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = MqttProductionHandler;
}