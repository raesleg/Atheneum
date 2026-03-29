document.addEventListener('DOMContentLoaded', function () {

    var picker = document.getElementById('starPicker');
    var ratingInput = document.getElementById('ratingInput');

    if (picker) {
        var stars = picker.querySelectorAll('.star-pick');
        var currentSelected = 0;

        stars.forEach(function (star) {
            star.addEventListener('click', function () {
                selectStar(parseInt(this.dataset.value));
            });

            star.addEventListener('keydown', function (e) {
                var val = parseInt(this.dataset.value);

                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectStar(val);
                } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    moveFocus(val, 1);
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    moveFocus(val, -1);
                }
            });

            star.addEventListener('mouseenter', function () {
                var val = parseInt(this.dataset.value);
                highlightStars(val);
            });

            star.addEventListener('mouseleave', function () {
                highlightStars(currentSelected);
            });
        });

        function moveFocus(currentVal, direction) {
            var nextVal = currentVal + direction;
            if (nextVal < 1) nextVal = 5;
            if (nextVal > 5) nextVal = 1;

            stars.forEach(function (s) {
                if (parseInt(s.dataset.value) === nextVal) {
                    s.setAttribute('tabindex', '0');
                    s.focus();
                } else {
                    s.setAttribute('tabindex', '-1');
                }
            });
        }

        function selectStar(val) {
            currentSelected = val;
            ratingInput.value = val;

            stars.forEach(function (s) {
                var sv = parseInt(s.dataset.value);
                var icon = s.querySelector('i');
                if (sv <= val) {
                    s.setAttribute('aria-checked', 'true');
                    icon.className = 'bi bi-star-fill';
                } else {
                    s.setAttribute('aria-checked', 'false');
                    icon.className = 'bi bi-star';
                }
                s.setAttribute('tabindex', sv === val ? '0' : '-1');
            });
        }

        function highlightStars(upTo) {
            stars.forEach(function (s) {
                var sv = parseInt(s.dataset.value);
                var icon = s.querySelector('i');
                if (sv <= upTo) {
                    icon.className = 'bi bi-star-fill';
                } else if (sv <= currentSelected) {
                    icon.className = 'bi bi-star-fill';
                } else {
                    icon.className = 'bi bi-star';
                }
            });
        }
    }

    var commentBox = document.getElementById('reviewComment');
    var charCount = document.getElementById('charCount');

    if (commentBox && charCount) {
        commentBox.addEventListener('input', function () {
            charCount.textContent = this.value.length;
        });
    }

    var form = document.getElementById('reviewForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var errorDiv = document.getElementById('reviewError');
            errorDiv.textContent = '';

            var rating = parseInt(ratingInput.value);
            if (rating < 1 || rating > 5) {
                errorDiv.textContent = 'Please select a star rating.';
                if (picker) picker.focus();
                return;
            }

            var comment = commentBox ? commentBox.value.trim() : '';
            if (comment.length > 200) {
                errorDiv.textContent = 'Comment must be 200 characters or fewer.';
                commentBox.focus();
                return;
            }

            var productId = parseInt(form.querySelector('[name="productId"]').value);
            var orderId = parseInt(form.querySelector('[name="orderId"]').value);

            var btn = form.querySelector('.submit-review-btn');
            btn.disabled = true;
            btn.textContent = 'Submitting...';

            fetch('process_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    productId: productId,
                    orderId: orderId,
                    rating: rating,
                    comment: comment
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    var formCard = document.getElementById('reviewFormCard');
                    formCard.innerHTML =
                        '<div class="review-notice" role="status" aria-live="polite">' +
                        '<i class="bi bi-check-circle" aria-hidden="true"></i> Your review has been submitted. Thank you!' +
                        '</div>';

                    var listEl = document.getElementById('reviewsList');
                    var noReviews = listEl.querySelector('.no-reviews');
                    if (noReviews) noReviews.remove();

                    var rev = data.review;
                    var starsHtml = '';
                    for (var i = 1; i <= 5; i++) {
                        starsHtml += '<i class="bi bi-star' + (i <= rev.rating ? '-fill' : '') + '" aria-hidden="true"></i>';
                    }

                    var card = document.createElement('article');
                    card.className = 'review-card new-review';
                    card.setAttribute('role', 'listitem');
                    card.setAttribute('aria-label', 'Review by ' + escapeHtml(rev.displayName) + ', ' + rev.rating + ' out of 5 stars');
                    card.innerHTML =
                        '<div class="review-header">' +
                        '<i class="bi bi-person-circle review-avatar" aria-hidden="true"></i>' +
                        '<div>' +
                        '<span class="reviewer-name">' + escapeHtml(rev.displayName) + '</span>' +
                        '<div class="stars-display stars-sm" aria-label="' + rev.rating + ' out of 5 stars">' + starsHtml + '</div>' +
                        '</div>' +
                        '<span class="review-date">' + rev.date + '</span>' +
                        '</div>' +
                        (rev.comment ? '<p class="review-comment">' + escapeHtml(rev.comment) + '</p>' : '');

                    listEl.insertBefore(card, listEl.firstChild);
                } else {
                    errorDiv.textContent = data.error || 'Something went wrong.';
                    btn.disabled = false;
                    btn.textContent = 'Submit Review';
                    btn.focus();
                }
            })
            .catch(function () {
                errorDiv.textContent = 'Network error. Please try again.';
                btn.disabled = false;
                btn.textContent = 'Submit Review';
                btn.focus();
            });
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});
