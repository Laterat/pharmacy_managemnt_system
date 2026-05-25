document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
    }

    document.querySelectorAll('.confirm-delete').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!confirm('Are you sure you want to delete this record?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.auto-submit').forEach((input) => {
        input.addEventListener('change', () => input.form.submit());
    });

    document.querySelectorAll('.validate-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const required = form.querySelectorAll('[required]');
            let valid = true;
            required.forEach((field) => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('field-error');
                } else {
                    field.classList.remove('field-error');
                }
            });
            if (!valid) {
                event.preventDefault();
                alert('Please complete all required fields.');
            }
        });
    });

    document.querySelectorAll('.print-btn').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });

    const saleForm = document.querySelector('.sale-form');
    if (saleForm) {
        const medicineSelect = saleForm.querySelector('#medicineSelect');
        const qtyInput = saleForm.querySelector('.sale-qty');
        const stockText = saleForm.querySelector('.available-stock');
        const priceText = saleForm.querySelector('.unit-price');
        const totalText = saleForm.querySelector('.sale-total');

        const updateSaleSummary = () => {
            const option = medicineSelect.selectedOptions[0];
            const price = option ? Number(option.dataset.price || 0) : 0;
            const stock = option ? Number(option.dataset.stock || 0) : 0;
            const qty = Number(qtyInput.value || 0);
            stockText.textContent = stock;
            priceText.textContent = price.toFixed(2);
            totalText.textContent = (price * qty).toFixed(2);
            qtyInput.max = stock || '';
        };

        medicineSelect.addEventListener('change', updateSaleSummary);
        qtyInput.addEventListener('input', updateSaleSummary);
        updateSaleSummary();
    }

    document.querySelectorAll('.client-search').forEach((input) => {
        const select = document.querySelector(input.dataset.target);
        if (!select) {
            return;
        }
        const options = Array.from(select.options);
        input.addEventListener('input', () => {
            const term = input.value.toLowerCase();
            options.forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }
                option.hidden = !option.textContent.toLowerCase().includes(term);
            });
        });
    });
});
