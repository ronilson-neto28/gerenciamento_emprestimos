document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('finance-modal');
    const openButtons = document.querySelectorAll('[data-open-finance-modal]');
    const closeButtons = document.querySelectorAll('[data-close-finance-modal]');
    const form = document.getElementById('finance-form');
    const dateInput = document.getElementById('finance-date');
    const dateDisplayInput = document.getElementById('finance-date-display');
    const valueInput = document.getElementById('finance-value');
    const typeSelect = document.getElementById('finance-type');
    const descriptionInput = document.getElementById('finance-description');

    if (!modal) {
        return;
    }

    const csrfToken = (() => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    })();

    const showToast = (message, variant = 'success') => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${variant}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });

        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 250);
        }, 1500);
    };

    const apiRequest = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(options.headers || {}),
            },
            ...options,
        });

        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ? await response.json() : null;

        if (!response.ok) {
            const message = data && typeof data.message === 'string' && data.message ? data.message : 'Não foi possível concluir a operação.';
            throw new Error(message);
        }

        return data;
    };

    const getTodayInputValue = () => {
        const now = new Date();
        const local = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
        return local.toISOString().slice(0, 10);
    };

    const isoToDisplayDate = (isoValue) => {
        const value = String(isoValue || '');
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) {
            return '';
        }
        return `${match[3]}/${match[2]}/${match[1]}`;
    };

    const normalizeDisplayDate = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        if (!digits) {
            return '';
        }
        const d = digits.slice(0, 2);
        const m = digits.slice(2, 4);
        const y = digits.slice(4, 8);
        if (digits.length <= 2) {
            return d;
        }
        if (digits.length <= 4) {
            return `${d}/${m}`;
        }
        return `${d}/${m}/${y}`;
    };

    const displayToIsoDate = (displayValue) => {
        const value = String(displayValue || '').trim();
        const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!match) {
            return '';
        }
        const day = match[1];
        const month = match[2];
        const year = match[3];
        return `${year}-${month}-${day}`;
    };

    const syncFinanceDateFields = () => {
        if (!dateInput || !dateDisplayInput) {
            return;
        }

        const normalizedDisplay = normalizeDisplayDate(dateDisplayInput.value);
        if (normalizedDisplay !== dateDisplayInput.value) {
            dateDisplayInput.value = normalizedDisplay;
        }

        const iso = displayToIsoDate(normalizedDisplay);
        if (iso) {
            dateInput.value = iso;
        }
    };

    const formatCurrencyValue = (value) => new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);

    const bindCurrencyMask = (input) => {
        if (!input) {
            return;
        }

        let rawDigits = '';

        const normalizeDigits = (digits) => {
            const normalized = String(digits || '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');
            return normalized;
        };

        const updateDisplay = () => {
            if (!rawDigits) {
                input.value = '';
                input.dataset.rawDigits = '';
                return;
            }

            const integerValue = Number.parseInt(rawDigits, 10);
            input.value = Number.isNaN(integerValue) ? '' : formatCurrencyValue(integerValue);
            input.dataset.rawDigits = rawDigits;
        };

        const setRawDigits = (digits) => {
            rawDigits = normalizeDigits(digits);
            updateDisplay();
        };

        setRawDigits(input.dataset.rawDigits || '');

        input.addEventListener('keydown', (event) => {
            if (event.ctrlKey || event.metaKey || event.altKey) {
                return;
            }

            if (event.key === 'Backspace') {
                rawDigits = rawDigits.slice(0, -1);
                updateDisplay();
                event.preventDefault();
                return;
            }

            if (event.key === 'Delete') {
                rawDigits = '';
                updateDisplay();
                event.preventDefault();
                return;
            }

            if (/^\d$/.test(event.key)) {
                rawDigits = normalizeDigits(rawDigits + event.key);
                updateDisplay();
                event.preventDefault();
                return;
            }

            if (event.key === 'Tab' || event.key === 'Enter' || event.key.startsWith('Arrow')) {
                return;
            }

            if (event.key.length === 1) {
                event.preventDefault();
            }
        });

        input.addEventListener('paste', (event) => {
            const text = event.clipboardData ? event.clipboardData.getData('text') : '';
            const digits = normalizeDigits(text);
            setRawDigits(digits);
            event.preventDefault();
        });

        input.addEventListener('blur', () => {
            updateDisplay();
        });
    };

    const openModal = () => {
        if (form) {
            form.reset();
            const todayIso = getTodayInputValue();
            if (dateInput) {
                dateInput.value = todayIso;
            }
            if (dateDisplayInput) {
                dateDisplayInput.value = isoToDisplayDate(todayIso);
            }
            if (typeSelect) {
                typeSelect.value = 'entrada';
                typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (valueInput) {
                valueInput.value = '';
                valueInput.dataset.rawDigits = '';
            }
        }
        modal.classList.add('active');
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        modal.classList.remove('active');
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    openButtons.forEach((button) => button.addEventListener('click', openModal));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    if (dateDisplayInput) {
        dateDisplayInput.addEventListener('input', syncFinanceDateFields);
        dateDisplayInput.addEventListener('blur', syncFinanceDateFields);
        dateDisplayInput.addEventListener('paste', () => window.setTimeout(syncFinanceDateFields, 0));
    }

    bindCurrencyMask(valueInput);

    if (form) {
        let isSubmitting = false;
        const submitButton = form.querySelector('button[type="submit"]');

        const setSubmitting = (submitting) => {
            isSubmitting = submitting;
            if (submitButton) {
                submitButton.disabled = submitting;
                submitButton.setAttribute('aria-busy', submitting ? 'true' : 'false');
            }
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (isSubmitting) {
                return;
            }
            syncFinanceDateFields();

            const payload = {
                _token: csrfToken,
                type: typeSelect ? typeSelect.value : 'entrada',
                value: valueInput && valueInput.dataset && valueInput.dataset.rawDigits ? valueInput.dataset.rawDigits : (valueInput ? valueInput.value : ''),
                date: dateInput ? dateInput.value : '',
                description: descriptionInput ? descriptionInput.value : '',
            };

            setSubmitting(true);

            try {
                await apiRequest('/admin/api/financeiro/lancamentos', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams(payload),
                });

                closeModal();
                showToast('Lançamento registrado.');
                window.setTimeout(() => window.location.reload(), 400);
            } catch (error) {
                showToast(error.message, 'danger');
                setSubmitting(false);
            }
        });
    }
});
