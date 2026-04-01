document.addEventListener('DOMContentLoaded', function () {
    const subjectSelect = document.getElementById('contactSubject');
    const refundSection = document.getElementById('refund-section');
    const orderSelect = document.getElementById('orderId');
    const detailsRow = document.getElementById('contact-details-row');
    const msgLabel = document.getElementById('msgLabel');
    const submitBtn = document.getElementById('submitBtn');

    // Refund Logic for Client-side validation: Message length >= 10
    const contactForm = document.querySelector('.contact-form');
    const messageInput = document.getElementById('contactMessage');

    if (contactForm && messageInput) {
        contactForm.addEventListener('submit', function (e) {
            const messageValue = messageInput.value.trim();
            if (messageValue.length < 10) {
                e.preventDefault();
                alert('Your message must be at least 10 characters long.');
                messageInput.focus();
            }
        });
    }

    if (subjectSelect) {
        subjectSelect.addEventListener('change', function () {
            if (this.value === 'refund') {
                if (refundSection) refundSection.style.display = 'block';

                if (orderSelect) {
                    orderSelect.style.display = 'block';
                    orderSelect.required = true;
                }

                if (detailsRow) detailsRow.style.display = 'none';
                if (msgLabel) msgLabel.textContent = 'Reason for Refund *';
                if (submitBtn) submitBtn.textContent = 'Submit Refund Request';
            } else {
                if (refundSection) refundSection.style.display = 'none';
                if (detailsRow) detailsRow.style.display = 'grid';
                if (msgLabel) msgLabel.textContent = 'Message *';
                if (submitBtn) submitBtn.textContent = 'Send Message';
                if (orderSelect) orderSelect.required = false;
            }
        });
    }
});