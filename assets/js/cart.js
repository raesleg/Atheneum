document.addEventListener('DOMContentLoaded', function () {

    const params = new URLSearchParams(window.location.search);
    if (params.get('paid') === '1') {
        window.history.replaceState(null, '', window.location.pathname);
        showPaymentToast();
    }

        function changeQty(id, delta) {
        qtys[id] = Math.max(1, (qtys[id] || 1) + delta);
        document.getElementById('qty-' + id).textContent = qtys[id];
        document.getElementById('total-' + id).textContent = '$' + (prices[id] * qtys[id]).toFixed(2);
        recalc();

        fetch('process_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', productId: id, qty: qtys[id] })
        });
    }

    function removeItem(id) {
        const el = document.getElementById('item-' + id);
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
                recalc();
                updateCount();
            }, 300);
        });
    }

    function recalc() {
        let subtotal = 0;
        for (const id in prices) subtotal += prices[id] * qtys[id];
        const shipping = subtotal === 0 ? 0 : (subtotal > 50 ? 0 : 4.99);
        const total = subtotal + shipping;

        document.getElementById('summary-subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('summary-shipping').innerHTML =
            shipping === 0 ? '<span class="free-shipping-note">Free</span>' : '$' + shipping.toFixed(2);
        document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    }

    document.getElementById('checkout-btn')?.addEventListener('click', async () => {
        try {
            const res = await fetch('create_checkout.php', {
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

        // Trigger entrance
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.add('show');
            });
        });

        // Auto-dismiss after 5s
        const timer = setTimeout(function () { dismiss(toast); }, 5000);

        // Manual close
        toast.querySelector('.pt-close').addEventListener('click', function () {
            clearTimeout(timer);
            dismiss(toast);
        });
    }

    function dismiss(toast) {
        toast.classList.remove('show');
        setTimeout(function () {
            if (toast && toast.parentNode) toast.remove();
        }, 500);
    }
});