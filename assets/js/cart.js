document.addEventListener('DOMContentLoaded', function () {

    // ── Payment toast on redirect back from Stripe ──
    const params = new URLSearchParams(window.location.search);
    if (params.get('paid') === '1') {
        window.history.replaceState(null, '', window.location.pathname);
        showPaymentToast();
    }

    // ── Qty error banner (shown inline below qty controls) ──
    function showQtyError(id, msg) {
        let err = document.getElementById('qty-err-' + id);
        if (!err) {
            err = document.createElement('p');
            err.id = 'qty-err-' + id;
            err.style.cssText = 'margin:6px 0 0;font-size:0.78rem;color:#c0524f;font-weight:500;';
            document.querySelector('#item-' + id + ' .qty-controls').after(err);
        }
        err.textContent = msg;
        clearTimeout(err._t);
        err._t = setTimeout(() => err.remove(), 3000);
    }

    function clearQtyError(id) {
        const err = document.getElementById('qty-err-' + id);
        if (err) err.remove();
    }

    // ── Change quantity ──
    function changeQty(id, delta) {
        const current = qtys[id] || 1;
        const next    = current + delta;

        // Reduce to 0 → remove item
        if (next <= 0) {
            removeItem(id);
            return;
        }

        // Exceeds stock
        if (next > stocks[id]) {
            showQtyError(id, 'Only ' + stocks[id] + ' in stock');
            return;
        }

        clearQtyError(id);
        qtys[id] = next;
        document.getElementById('qty-'   + id).textContent = next;
        document.getElementById('total-' + id).textContent = '$' + (prices[id] * next).toFixed(2);
        recalc();

        fetch('process_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', productId: id, qty: next })
        });
    }

    // ── Remove item ──
    function removeItem(id) {
        const el = document.getElementById('item-' + id);
        if (!el) return;

        el.classList.add('removing');

        fetch('process_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', productId: id })
        }).then(() => {
            setTimeout(() => {
                el.remove();
                delete prices[id];
                delete qtys[id];
                delete stocks[id];
                recalc();
                updateItemCount();
            }, 300);
        });
    }

    // ── Update the "X items" label and navbar badge ──
    function updateItemCount() {
        const remaining = Object.keys(qtys).length;

        // Section label "3 items"
        const label = document.querySelector('.section-label');
        if (label) {
            label.textContent = remaining + ' item' + (remaining !== 1 ? 's' : '');
        }

        // Navbar cart badge
        const badge = document.getElementById('cart-count');
        if (badge) {
            const total = Object.values(qtys).reduce((a, b) => a + b, 0);
            badge.textContent = total;
        }

        // If cart is now empty, show empty state without full page reload
        if (remaining === 0) {
            const wrapper = document.querySelector('.cart-wrapper');
            if (wrapper) {
                wrapper.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-bag"></i>
                        <p>Your cart is empty.</p>
                        <a href="index.php" class="checkout-btn">Continue Browsing</a>
                    </div>`;
            }
        }
    }

    // ── Recalculate summary panel ──
    function recalc() {
        let subtotal = 0;
        for (const id in prices) subtotal += prices[id] * (qtys[id] || 0);
        const shipping = subtotal === 0 ? 0 : (subtotal > 50 ? 0 : 4.99);
        const total    = subtotal + shipping;

        document.getElementById('summary-subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('summary-shipping').innerHTML   =
            shipping === 0
                ? '<span class="free-shipping-note">Free</span>'
                : '$' + shipping.toFixed(2);
        document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    }

    // ── Checkout ──
    document.getElementById('checkout-btn')?.addEventListener('click', async () => {
        try {
            const res  = await fetch('create_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();

            if (data.success && data.url) {
                window.location.href = data.url;
            } else {
                alert(data.error || 'Could not start checkout.');
            }
        } catch (err) {
            alert('Checkout failed.');
            console.error(err);
        }
    });

    // ── Payment success toast ──
    function showPaymentToast() {
        const toast = document.createElement('div');
        toast.id = 'payment-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');

        toast.innerHTML = `
            <i class="bi bi-check-circle-fill pt-icon" aria-hidden="true"></i>
            <div class="pt-body">
                <div class="pt-title">Payment Successful!</div>
                <div class="pt-sub">Your order has been placed. <a href="orders.php">View your orders</a></div>
            </div>
            <button class="pt-close" aria-label="Dismiss">&times;</button>
            <div class="pt-progress"></div>
        `;

        document.body.appendChild(toast);

        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));

        const timer = setTimeout(() => dismiss(toast), 5000);
        toast.querySelector('.pt-close').addEventListener('click', () => {
            clearTimeout(timer);
            dismiss(toast);
        });
    }

    function dismiss(toast) {
        toast.classList.remove('show');
        setTimeout(() => { if (toast?.parentNode) toast.remove(); }, 500);
    }

    // Expose to inline onclick handlers in PHP
    window.changeQty  = changeQty;
    window.removeItem = removeItem;

});