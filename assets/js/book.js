document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('addToCartBtn');
    if (!btn) return;

    // Book data is passed via data attributes on the button
    var bookTitle = btn.dataset.bookTitle   || 'This book';
    var bookCover = btn.dataset.bookCover   || '';

    btn.addEventListener('click', function () {
        var pid = parseInt(btn.dataset.productId);

        // Disable button while request is in flight
        btn.disabled = true;

        fetch('process_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', productId: pid, qty: 1 })
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json();
        })
        .then(function (data) {
            btn.disabled = false;

            if (!data.success) {
                console.warn('Cart response:', data);
                return;
            }

            showToast(bookTitle, bookCover);
            updateCartBadge(1);
        })
        .catch(function (err) {
            btn.disabled = false;
            console.error('Add to cart failed:', err);
        });
    });

    function showToast(title, cover) {
        // Remove any existing toast
        var old = document.getElementById('cart-toast');
        if (old) old.remove();

        var toast = document.createElement('div');
        toast.id = 'cart-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');

        var thumbHtml = cover
            ? '<img class="toast-thumb" src="' + cover + '" alt="" '
                + 'onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">'
                + '<div class="toast-thumb-fallback" style="display:none"><i class="bi bi-book-fill"></i></div>'
            : '<div class="toast-thumb-fallback"><i class="bi bi-book-fill"></i></div>';

        toast.innerHTML = thumbHtml
            + '<div class="toast-body">'
            +   '<div class="toast-label">Added to Cart</div>'
            +   '<div class="toast-title">' + escapeHtml(title) + '</div>'
            + '</div>'
            + '<i class="bi bi-check-circle-fill toast-check" aria-hidden="true"></i>'
            + '<div class="toast-progress"></div>';

        document.body.appendChild(toast);

        // Trigger entrance on next frame
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.add('show');
            });
        });

        // Auto-dismiss after 3s
        var timer = setTimeout(function () { dismiss(toast); }, 3000);

        // Click anywhere on toast to dismiss early
        toast.addEventListener('click', function () {
            clearTimeout(timer);
            dismiss(toast);
        });
    }

    function dismiss(toast) {
        toast.classList.remove('show');
        setTimeout(function () {
            if (toast && toast.parentNode) toast.remove();
        }, 400);
    }

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});