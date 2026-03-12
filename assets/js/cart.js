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

function updateCount() {
    const remaining = Object.keys(prices).length;
    document.getElementById('cart-count').textContent = remaining;
}