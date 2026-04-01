document.addEventListener('DOMContentLoaded', function () {

    // Payment toast on redirect back from Stripe 
    const params = new URLSearchParams(window.location.search);
    if (params.get('paid') === '1') {
        window.history.replaceState(null, '', window.location.pathname);
        showPaymentToast();
    }

    // State
    let selectedAddressId = null;
    let addressesCache    = null;
 
    // Modal elements
    const overlay       = document.getElementById('addrOverlay');
    const closeBtn      = document.getElementById('closeAddrModal');
    const listView      = document.getElementById('addrListView');
    const formView      = document.getElementById('addrFormView');
    const addrCards     = document.getElementById('addrCards');
    const showFormBtn   = document.getElementById('showAddrForm');
    const backBtn       = document.getElementById('backToList');
    const cancelFormBtn = document.getElementById('cancelAddrForm');
    const confirmBtn    = document.getElementById('confirmAddrBtn');
    const selectError   = document.getElementById('addrSelectError');
    const addressForm   = document.getElementById('addressForm');
    const formError     = document.getElementById('addrFormError');
 
    // modal
    function openModal() {
        overlay.hidden = false;
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        showListView();
        loadAddresses();
        closeBtn?.focus();
    }
    function closeModal() {
        overlay.classList.remove('show');
        overlay.hidden = true;
        document.body.style.overflow = '';
    }
 
    document.getElementById('checkout-btn')?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && overlay?.classList.contains('show')) closeModal();
    });
 
    function showListView() {
        listView.style.display = 'block';
        formView.style.display = 'none';
        selectError.textContent = '';
    }
 
    function showFormView() {
        listView.style.display = 'none';
        formView.style.display = 'block';
        formError.textContent = '';
        addressForm.reset();

        const saveBtn = document.getElementById('saveAddrBtn');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Address';

        document.getElementById('addr_label').focus();
    }
 
    showFormBtn?.addEventListener('click', showFormView);
    backBtn?.addEventListener('click', showListView);
    cancelFormBtn?.addEventListener('click', showListView);
 
    async function loadAddresses() {
        addrCards.innerHTML = '<div class="addr-loading"><i class="bi bi-arrow-repeat addr-spin"></i> Loading…</div>';
        showFormBtn.style.display = 'none';

        try {
            const res  = await fetch('process_address.php');

            let data = null;
            try {
                const text = await res.text();
                const jsonStart = text.indexOf('{');
                data = JSON.parse(jsonStart > -1 ? text.slice(jsonStart) : text);
            } catch (_) {
                throw new Error('Invalid response from server');
            }

            if (!data.success) throw new Error(data.error);

            addressesCache = data.addresses;

            if (data.addresses.length === 0) {
                addrCards.innerHTML = '<p class="addr-empty-note">You have no saved addresses.</p>';
            } else {
                renderAddressCards(data.addresses);
            }

            showFormBtn.style.display = 'flex';

        } catch (err) {
            addrCards.innerHTML = '<p class="addr-select-error">Could not load addresses. Please try again.</p>';
            showFormBtn.style.display = 'flex';
        }
    } 

    function renderAddressCards(addresses) {
        if (addresses.length === 0) {
            addrCards.innerHTML = '';
            return;
        }

        addrCards.innerHTML = addresses.map(a => {
            const line2 = a.address_line2 ? ', ' + escHtml(a.address_line2) : '';
            const stateZip = [a.state, a.postal_code].filter(Boolean).join(' ');
            const isSelected = selectedAddressId === a.addressId;

            return `
                <div class="addr-card ${isSelected ? 'selected' : ''}" data-id="${a.addressId}">
                    <button
                        type="button"
                        class="addr-card-select"
                        data-id="${a.addressId}"
                        aria-pressed="${isSelected ? 'true' : 'false'}"
                        aria-label="Select address ${escHtml(a.label)}"
                    >
                        <div class="addr-card-radio" aria-hidden="true">
                            <div class="addr-radio-dot"></div>
                        </div>

                        <div class="addr-card-body">
                            <p class="addr-card-name">${escHtml(a.label)}</p>
                            <p class="addr-card-line">${escHtml(a.address_line1)}${line2}</p>
                            <p class="addr-card-line">${escHtml(a.city)}${stateZip ? ', ' + escHtml(stateZip) : ''}</p>
                            <p class="addr-card-line">${escHtml(a.country)}</p>
                        </div>

                        <i class="bi bi-check-circle-fill addr-card-check" aria-hidden="true"></i>
                    </button>

                    <button
                        type="button"
                        class="addr-delete-btn"
                        data-id="${a.addressId}"
                        aria-label="Delete address ${escHtml(a.label)}"
                        title="Delete"
                    >
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                </div>`;
        }).join('');

        addrCards.querySelectorAll('.addr-card-select').forEach(btn => {
            const activate = () => {
                selectedAddressId = parseInt(btn.dataset.id, 10);

                addrCards.querySelectorAll('.addr-card-select').forEach(cardBtn => {
                    cardBtn.setAttribute('aria-pressed', 'false');
                });

                btn.closest('.addr-card')?.classList.add('selected');
                btn.setAttribute('aria-pressed', 'true');
                selectError.textContent = '';
            };

            btn.addEventListener('click', activate);
        });

        addrCards.querySelectorAll('.addr-delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();

                const id = parseInt(btn.dataset.id, 10);
                try {
                    const res = await fetch('process_address.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ addressId: id })
                    });

                    const text = await res.text();
                    const jsonStart = text.indexOf('{');
                    const data = JSON.parse(jsonStart > -1 ? text.slice(jsonStart) : text);

                    if (!data.success) {
                        alert('Could not delete address.');
                        return;
                    }

                    addressesCache = addressesCache.filter(a => a.addressId !== id);
                    if (selectedAddressId === id) selectedAddressId = null;
                    renderAddressCards(addressesCache);

                } catch (err) {
                    alert('Could not delete address.');
                }
            });
        });
    }
 
    // Save new address
    addressForm?.addEventListener('submit', async function (e) {
        e.preventDefault();
        formError.textContent = '';

        const saveBtn = document.getElementById('saveAddrBtn');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';

        const payload = {
            label: addressForm.label.value.trim(),
            address_line1: addressForm.address_line1.value.trim(),
            address_line2: addressForm.address_line2.value.trim(),
            city: addressForm.city.value.trim(),
            state: addressForm.state.value.trim(),
            postal_code: addressForm.postal_code.value.trim(),
            country: addressForm.country.value.trim(),
        };

        try {
            const res = await fetch('process_address.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!data.success) {
                formError.textContent = data.error || 'Could not save address.';
                return;
            }

            if (!addressesCache) addressesCache = [];
            addressesCache.unshift(data.address);
            selectedAddressId = data.address.addressId;

            showFormBtn.style.display = 'flex';
            renderAddressCards(addressesCache);
            showListView();
            addressForm.reset();

        } catch (err) {
            formError.textContent = 'Something went wrong. Please try again.';
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Address';
        }
    }); 

    // Confirm address & proceed to Stripe
    confirmBtn?.addEventListener('click', async () => {
        if (!selectedAddressId) {
            selectError.textContent = 'Please select a shipping address to continue.';
            return;
        }
 
        confirmBtn.disabled    = true;
        confirmBtn.textContent = 'Redirecting…';
 
        try {
            const res  = await fetch('create_checkout.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ addressId: selectedAddressId })
            });
            const data = await res.json();
 
            if (data.success && data.url) {
                window.location.href = data.url;
            } else {
                selectError.textContent = data.error || 'Could not start checkout.';
                confirmBtn.disabled    = false;
                confirmBtn.textContent = 'Continue to Payment';
            }
        } catch (err) {
            selectError.textContent = 'Checkout failed. Please try again.';
            confirmBtn.disabled    = false;
            confirmBtn.textContent = 'Continue to Payment';
        }
    });

    // Qty error banner (shown inline below qty controls)
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

    // Change quantity 
    function changeQty(id, delta) {
        const current = Number(qtys[id] ?? 0);
        const next = current + delta;

        if (next <= 0) {
            removeItem(id);
            return;
        }

        if (next > stocks[id]) {
            showQtyError(id, 'Only ' + stocks[id] + ' in stock');
            return;
        }

        clearQtyError(id);
        qtys[id] = next;


        fetch('process_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', productId: id, qty: next })
        }).then(() => {
            document.getElementById('qty-' + id).textContent = next;
            document.getElementById('total-' + id).textContent = '$' + (prices[id] * next).toFixed(2);
            recalc();
            updateItemCount();
        });
    }
    
    // Remove item
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

    function updateItemCount() {
        let remaining = 0;
        for (const id in qtys) {
            remaining += Number(qtys[id]) || 0;
        }

        const label = document.getElementById('cart-item-count');
        if (label) {
            label.textContent = remaining + ' item' + (remaining !== 1 ? 's' : '');
        }

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

    // summary panel
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

    // Payment success toast
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

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    updateItemCount();
    window.changeQty  = changeQty;
    window.removeItem = removeItem;

});