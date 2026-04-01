document.addEventListener('DOMContentLoaded', function () {
    const subjectSelect = document.getElementById('contactSubject');
    const refundSection = document.getElementById('refund-section');
    const orderSelect = document.getElementById('orderId');
    const detailsRow = document.getElementById('contact-details-row');
    const msgLabel = document.getElementById('msgLabel');
    const submitBtn = document.getElementById('submitBtn');

    if (subjectSelect) {
        subjectSelect.addEventListener('change', function () {
            if (this.value === 'refund') {
                // Show the section
                refundSection.style.display = 'block';
                // FORCE the dropdown to show
                if (orderSelect) {
                    orderSelect.style.display = 'block';
                    orderSelect.required = true;
                }

                detailsRow.style.display = 'none';
                msgLabel.textContent = 'Reason for Refund *';
                submitBtn.textContent = 'Submit Refund Request';
            } else {
                refundSection.style.display = 'none';
                detailsRow.style.display = 'grid';
                msgLabel.textContent = 'Message *';
                submitBtn.textContent = 'Send Message';
                if (orderSelect) orderSelect.required = false;
            }
        });
    }
});