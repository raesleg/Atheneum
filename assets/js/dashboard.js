(function () {
    let activeRefundId = null;
    let previousFocus = null;

    function getFocusable() {
        return Array.from(document.getElementById('refund-panel')
            .querySelectorAll('button:not([disabled]), textarea, [tabindex="0"]'));
    }

    function trapFocus(e) {
        if (e.key !== 'Tab') return;
        const els = getFocusable();
        const first = els[0];
        const last = els[els.length - 1];
        if (e.shiftKey) {
            if (document.activeElement === first) { e.preventDefault(); last.focus(); }
        } else {
            if (document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    }

    window.openRefundPanel = function (btn) {
        const data = JSON.parse(btn.dataset.refund);
        activeRefundId = data.refundId;
        previousFocus = btn;

        const typeLower = data.type.toLowerCase();
        const isRefundType = typeLower === 'refund';
        const isFeedbackType = typeLower === 'feedback';

        // Reset visibility
        document.querySelectorAll('.rp-only-refund').forEach(el => el.style.display = (isRefundType ? '' : 'none'));
        document.querySelectorAll('.rp-only-enquiry').forEach(el => el.style.display = (!isRefundType ? '' : 'none'));
        document.getElementById('rp-reject-btn').style.display = (isRefundType ? '' : 'none');

        if (isRefundType) {
            document.getElementById('rp-title').textContent = 'Refund Request';
            document.getElementById('rp-username').textContent = data.username;
            document.getElementById('rp-orderid').textContent = '#' + data.orderId;
            document.getElementById('rp-amount').textContent = '$' + parseFloat(data.totalPrice).toFixed(2);
            document.getElementById('rp-reason-label').textContent = "Customer's Reason";
            document.getElementById('rp-approve-text').textContent = "Approve Refund";
            document.getElementById('rp-note-label').innerHTML = 'Message to Customer <span class="rp-note-hint">(optional — visible on their Order History)</span>';
        } else {
            document.getElementById('rp-title').textContent = isFeedbackType ? 'User Feedback' : 'General Enquiry';
            document.getElementById('rp-username').textContent = data.name || data.reg_username || 'Guest';
            document.getElementById('rp-subject').textContent = data.subject || (isFeedbackType ? 'Feedback' : 'Enquiry');
            document.getElementById('rp-reason-label').textContent = isFeedbackType ? "User's Feedback" : "Enquiry Message";
            document.getElementById('rp-approve-text').textContent = "Send Reply";
            document.getElementById('rp-note-label').innerHTML = 'Reply to User <span class="rp-note-hint">(will be sent to their email)</span>';
        }

        document.getElementById('rp-reason').textContent = data.reason;
        document.getElementById('rp-note').value = '';
        const fb = document.getElementById('rp-feedback');
        fb.textContent = '';
        fb.className = 'rp-feedback';

        const d = new Date(data.created_at.replace(' ', 'T'));
        document.getElementById('rp-date').textContent =
            d.toLocaleDateString('en-SG', { day: '2-digit', month: 'short', year: 'numeric' });

        document.getElementById('rp-approve-btn').disabled = false;
        document.getElementById('rp-reject-btn').disabled = false;

        const panel = document.getElementById('refund-panel');
        const backdrop = document.getElementById('refund-backdrop');

        panel.setAttribute('aria-hidden', 'false');
        panel.classList.add('open');
        backdrop.setAttribute('aria-hidden', 'false');
        backdrop.classList.add('open');
        document.body.classList.add('panel-open');

        document.addEventListener('keydown', handlePanelKey);

        requestAnimationFrame(() => getFocusable()[0]?.focus());
    };

    window.closeRefundPanel = function () {
        const panel = document.getElementById('refund-panel');
        const backdrop = document.getElementById('refund-backdrop');

        panel.classList.remove('open');
        backdrop.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('panel-open');

        document.removeEventListener('keydown', handlePanelKey);
        activeRefundId = null;

        previousFocus?.focus();
    };

    function handlePanelKey(e) {
        if (e.key === 'Escape') closeRefundPanel();
        else trapFocus(e);
    }

    window.submitRefundAction = async function (action) {
        if (!activeRefundId) return;

        const approveBtn = document.getElementById('rp-approve-btn');
        const rejectBtn = document.getElementById('rp-reject-btn');
        const feedback = document.getElementById('rp-feedback');
        const note = document.getElementById('rp-note').value.trim();

        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        feedback.textContent = '';
        feedback.className = 'rp-feedback';

        try {
            const res = await fetch('refund_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    refundId: activeRefundId,
                    action,
                    adminNote: note,
                    csrf_token: CSRF_TOKEN
                })
            });
            const data = await res.json();
            if (data.new_csrf_token) CSRF_TOKEN = data.new_csrf_token;

            if (data.success) {
                const btnData = JSON.parse(previousFocus.dataset.refund);
                const isRefund = btnData.type === 'Refund';

                if (isRefund) {
                    feedback.textContent = action === 'approve'
                        ? '✓ Refund approved. The order has been marked as refunded.'
                        : '✓ Request rejected.';
                } else {
                    feedback.textContent = action === 'approve'
                        ? '✓ Reply sent successfully via email.'
                        : '✓ Enquiry dismissed.';
                }
                feedback.classList.add('rp-feedback-success');

                // Remove the resolved row from the list
                previousFocus.closest('li').remove();

                // Update badges and empty states
                const listClass = isRefund ? '.refund-list-box' : '.enquiry-list-box';
                const rowClass = isRefund ? '.refund-row:not(.enquiry-row)' : '.enquiry-row';
                const badgeClass = isRefund ? '.count-badge:not(.enquiry-badge)' : '.enquiry-badge';
                const emptyText = isRefund ? 'No refund requests at this time.' : 'No enquiries at this time.';

                const remaining = document.querySelectorAll(rowClass);
                if (remaining.length === 0) {
                    const box = document.querySelector(listClass);
                    if (box) {
                        box.outerHTML =
                            '<div class="empty-state" role="status">' +
                            '<i class="bi bi-check-circle" style="font-size:1.8rem;color:var(--success);display:block;margin-bottom:8px" aria-hidden="true"></i>' +
                            emptyText + '</div>';
                    }
                    const badge = document.querySelector(badgeClass);
                    if (badge) badge.remove();
                } else {
                    const badge = document.querySelector(badgeClass);
                    if (badge) badge.textContent = remaining.length;
                }

                setTimeout(closeRefundPanel, 1400);
            } else {
                feedback.textContent = data.message || 'Something went wrong.';
                feedback.classList.add('rp-feedback-error');
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
            }
        } catch (err) {
            feedback.textContent = 'Network error. Please try again.';
            feedback.classList.add('rp-feedback-error');
            approveBtn.disabled = false;
            rejectBtn.disabled = false;
        }
    };
})();
