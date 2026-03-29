var POLL_INTERVAL_MS = 5000;

document.addEventListener('DOMContentLoaded', function () {

    if (typeof window.SHIPMENT_CONFIG === 'undefined') return;

    var config = window.SHIPMENT_CONFIG;
    var currentStatus = config.currentStatus;

    if (currentStatus === 'delivered') return;

    var pollTimer = setInterval(pollStatus, POLL_INTERVAL_MS);

    function pollStatus() {
        fetch('process_shipment.php?orderId=' + config.orderId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                if (data.status !== currentStatus) {
                    currentStatus = data.status;
                    updateTrackerUI(data);
                    announceStatusChange(data.label);

                    if (currentStatus === 'delivered') {
                        clearInterval(pollTimer);
                    }
                }
            })
            .catch(function (err) {
                console.log('Shipment poll error:', err);
            });
    }

    function announceStatusChange(label) {
        var announcement = document.getElementById('statusAnnouncement');
        if (announcement) {
            announcement.textContent = 'Shipment status updated to: ' + label;
        }
    }

    function updateTrackerUI(data) {
        var statusIdx = data.statusIdx;

        config.statusOrder.forEach(function (step, idx) {
            var stepEl = document.getElementById('step-' + step);
            var connectorEl = document.getElementById('connector-' + step);
            var timeEl = document.getElementById('time-' + step);

            if (!stepEl) return;

            stepEl.classList.remove('completed', 'current');

            var stepState;
            if (idx < statusIdx) {
                stepEl.classList.add('completed');
                stepState = 'Completed';
            } else if (idx === statusIdx) {
                stepEl.classList.add('completed', 'current');
                stepState = 'Current step';
            } else {
                stepState = 'Upcoming';
            }

            var timeText = data.timestamps[step] ? ', ' + data.timestamps[step] : '';
            stepEl.setAttribute('aria-label', config.statusLabels[step] + ': ' + stepState + timeText);

            if (connectorEl) {
                connectorEl.classList.remove('completed');
                if (idx < statusIdx) {
                    connectorEl.classList.add('completed');
                }
            }

            if (timeEl && data.timestamps[step]) {
                timeEl.innerHTML = '<time>' + data.timestamps[step] + '</time>';
            }
        });

        var headerPill = document.getElementById('headerStatusPill');
        if (headerPill) {
            config.statusOrder.forEach(function (s) {
                headerPill.classList.remove(s.replace(/_/g, '-'));
            });
            headerPill.classList.add(data.status.replace(/_/g, '-'));
            headerPill.textContent = data.label;
        }

        var gifContainer = document.getElementById('transitGif');
        if (gifContainer) {
            if (data.status === 'in_transit') {
                gifContainer.classList.add('visible');
            } else {
                gifContainer.classList.remove('visible');
            }
        }

        if (data.status === 'delivered') {
            setTimeout(function () {
                location.reload();
            }, 1500);
        }
    }
});
