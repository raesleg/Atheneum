var POLL_INTERVAL_MS = 5000;

document.addEventListener('DOMContentLoaded', function () {

    if (typeof window.SHIPMENT_CONFIG !== 'undefined') {
        initDetailPagePolling();
    }

    if (typeof window.ORDERS_LIST_CONFIG !== 'undefined') {
        initListPagePolling();
    }

    /* ── Orders List Page Polling ── */
    function initListPagePolling() {
        var listConfig = window.ORDERS_LIST_CONFIG;
        var cards = document.querySelectorAll('.order-card[data-order-id]');
        var activeOrders = {};

        cards.forEach(function (card) {
            var status = card.getAttribute('data-shipment-status');
            if (status && status !== 'delivered') {
                var orderId = card.getAttribute('data-order-id');
                activeOrders[orderId] = { card: card, status: status };
            }
        });

        if (Object.keys(activeOrders).length === 0) return;

        setInterval(function () {
            Object.keys(activeOrders).forEach(function (orderId) {
                var entry = activeOrders[orderId];

                fetch('process_shipment.php?orderId=' + orderId)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.success) return;
                        if (data.status === entry.status) return;

                        entry.status = data.status;
                        entry.card.setAttribute('data-shipment-status', data.status);

                        // Update status pill
                        var pill = entry.card.querySelector('.status-pill');
                        if (pill) {
                            listConfig.statusOrder.forEach(function (s) {
                                pill.classList.remove(s.replace(/_/g, '-'));
                            });
                            pill.classList.add(data.status.replace(/_/g, '-'));
                            pill.textContent = data.label;
                        }

                        // Update mini-tracker dots
                        var dots = entry.card.querySelectorAll('.mini-step');
                        dots.forEach(function (dot, idx) {
                            dot.classList.remove('completed', 'current');
                            if (idx < data.statusIdx) {
                                dot.classList.add('completed');
                            } else if (idx === data.statusIdx) {
                                dot.classList.add('completed', 'current');
                            }

                            var line = dot.querySelector('.mini-line');
                            if (line) {
                                line.classList.remove('completed');
                                if (idx < data.statusIdx) {
                                    line.classList.add('completed');
                                }
                            }
                        });

                        // Update progressbar aria
                        var tracker = entry.card.querySelector('.mini-tracker');
                        if (tracker) {
                            tracker.setAttribute('aria-valuenow', data.statusIdx + 1);
                            tracker.setAttribute('aria-label',
                                'Shipment progress: ' + data.label +
                                ', step ' + (data.statusIdx + 1) + ' of ' + listConfig.statusOrder.length
                            );
                        }

                        if (data.status === 'delivered') {
                            delete activeOrders[orderId];
                        }
                    })
                    .catch(function (err) {
                        console.log('List poll error for order ' + orderId + ':', err);
                    });
            });
        }, POLL_INTERVAL_MS);
    }

    /* ── Order Detail Page Polling ── */
    function initDetailPagePolling() {

        var config = window.SHIPMENT_CONFIG;
        var currentStatus = config.currentStatus;

        if (currentStatus === 'delivered') return;

        var pollTimer = setInterval(pollStatus, POLL_INTERVAL_MS);

        function pollStatus() {
            fetch('process_shipment.php?orderId=' + config.orderId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) return;

                    // If order has been refunded, stop polling and refresh the page to show refund details
                    if (data.isRefunded) {
                        clearInterval(pollTimer);
                        location.reload();
                        return;
                    }

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

            if (data.status === 'delivered') {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        }
    } /* end initDetailPagePolling */
});