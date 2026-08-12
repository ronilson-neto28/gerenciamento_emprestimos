document.addEventListener('DOMContentLoaded', () => {
    (() => {
        const modal = document.getElementById('loan-modal');
        const installmentsModal = document.getElementById('installments-modal');
        const receiveModal = document.getElementById('receive-modal');
        /* ============================================================
           MODAL CUSTOMIZADO DE CONFIRMAÇÃO DE EXCLUSÃO
           (substitui window.confirm do navegador — Design System KeneddyAdmin)
           ============================================================ */
        const deleteLoanModal = document.getElementById('delete-loan-modal');
        const deleteLoanTargetNameEl = document.getElementById('delete-loan-target-name');
        const deleteLoanBtnCancel = document.getElementById('delete-loan-btn-cancel');
        const deleteLoanBtnSubmit = document.getElementById('delete-loan-btn-submit');
        const closeDeleteLoanModalButtons = document.querySelectorAll('[data-close-delete-loan-modal]');
        // Estado: qual linha/emprestimo está sendo excluído. Evita uso de window.confirm.
        let pendingDeleteRow = null;
        let pendingDeleteBtn = null;

        const openDeleteLoanModal = (row, deleteBtn) => {
            if (!deleteLoanModal || !row) {
                return;
            }
            let loan = null;
            try {
                loan = row.dataset.loan ? JSON.parse(row.dataset.loan) : null;
            } catch (error) {
                loan = null;
            }
            const clientName = (loan && typeof loan.cliente === 'string' && loan.cliente.trim())
                ? loan.cliente.trim()
                : 'este empréstimo';
            if (deleteLoanTargetNameEl) {
                deleteLoanTargetNameEl.textContent = clientName;
            }
            // armazena estado atual
            pendingDeleteRow = row;
            pendingDeleteBtn = deleteBtn || null;
            // zera o botão de submit (caso anterior tenha ficado disabled)
            if (deleteLoanBtnSubmit instanceof HTMLButtonElement) {
                deleteLoanBtnSubmit.disabled = false;
                deleteLoanBtnSubmit.textContent = 'Sim, Excluir';
            }
            deleteLoanModal.dataset.targetId = row.dataset.loanId || '';
            deleteLoanModal.dataset.targetName = clientName;
            deleteLoanModal.classList.add('active');
            deleteLoanModal.classList.add('is-open');
            deleteLoanModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            // foco no botão cancelar para evitar exclusão por acidente com Enter/space
            try {
                if (deleteLoanBtnCancel && typeof deleteLoanBtnCancel.focus === 'function') {
                    deleteLoanBtnCancel.focus({ preventScroll: true });
                }
            } catch (error) { /* noop */ }
        };

        const closeDeleteLoanModal = () => {
            if (!deleteLoanModal) {
                return;
            }
            deleteLoanModal.classList.remove('active');
            deleteLoanModal.classList.remove('is-open');
            deleteLoanModal.setAttribute('aria-hidden', 'true');
            deleteLoanModal.dataset.targetId = '';
            deleteLoanModal.dataset.targetName = '';
            pendingDeleteRow = null;
            pendingDeleteBtn = null;
            // Restaura body overflow somente se nenhum outro modal estiver aberto
            const anyOpen = document.querySelector('.kmodal-overlay.active, .kmodal-overlay.is-open, .modal-overlay.active, .modal-overlay.is-open');
            if (!anyOpen) {
                document.body.style.overflow = '';
            }
        };

        const executePendingDelete = async () => {
            const row = pendingDeleteRow;
            const deleteBtn = pendingDeleteBtn;
            const loanId = row && row.dataset ? (row.dataset.loanId || '') : '';
            if (!row || !loanId) {
                closeDeleteLoanModal();
                return;
            }

            // desativa o botão de confirmação durante a requisição
            if (deleteLoanBtnSubmit instanceof HTMLButtonElement) {
                deleteLoanBtnSubmit.disabled = true;
                deleteLoanBtnSubmit.textContent = 'Excluindo...';
            }
            // desativa também o botão original da linha (para manter o comportamento antigo)
            if (deleteBtn instanceof HTMLButtonElement) {
                deleteBtn.disabled = true;
            }

            try {
                await apiRequest(`/admin/api/emprestimos/${loanId}`, { method: 'DELETE' });
                // Fecha modal ANTES de remover a linha (evita reflow enquanto overlay está ativo)
                closeDeleteLoanModal();
                row.remove();
                updateLoanCounters();
                showToast('Empréstimo excluído com sucesso.');
            } catch (error) {
                if (deleteLoanBtnSubmit instanceof HTMLButtonElement) {
                    deleteLoanBtnSubmit.disabled = false;
                    deleteLoanBtnSubmit.textContent = 'Sim, Excluir';
                }
                if (deleteBtn instanceof HTMLButtonElement) {
                    deleteBtn.disabled = false;
                }
                showToast(error instanceof Error ? error.message : 'Erro ao excluir empréstimo.', 'danger');
            }
        };

        // Bind global de todos os triggers de fechamento do modal de exclusão
        if (closeDeleteLoanModalButtons && closeDeleteLoanModalButtons.length) {
            closeDeleteLoanModalButtons.forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }
                    closeDeleteLoanModal();
                });
            });
        }
        if (deleteLoanBtnCancel) {
            deleteLoanBtnCancel.addEventListener('click', () => closeDeleteLoanModal());
        }
        if (deleteLoanBtnSubmit) {
            deleteLoanBtnSubmit.addEventListener('click', () => {
                void executePendingDelete();
            });
        }
        // Clique fora do card (overlay): fecha.
        if (deleteLoanModal) {
            deleteLoanModal.addEventListener('click', (event) => {
                const target = event && event.target instanceof Element ? event.target : null;
                if (target && target === deleteLoanModal) {
                    closeDeleteLoanModal();
                }
            });
        }
        // ESC fecha o modal de exclusão
        document.addEventListener('keydown', (event) => {
            if (!event) {
                return;
            }
            if (event.key === 'Escape'
                && deleteLoanModal
                && deleteLoanModal.getAttribute('aria-hidden') === 'false') {
                event.preventDefault();
                event.stopPropagation();
                closeDeleteLoanModal();
            }
        }, true);

        const openButton = document.querySelector('[data-open-loan-modal]');
        const closeButtons = document.querySelectorAll('[data-close-loan-modal]');
        const closeInstallmentsButtons = document.querySelectorAll('[data-close-installments-modal]');
        const closeReceiveButtons = document.querySelectorAll('[data-close-receive-modal]');
        const form = document.getElementById('loan-form');
        const editButtons = document.querySelectorAll('[data-edit-loan]');
        const deleteButtons = document.querySelectorAll('[data-delete-loan]');
        const installmentsButtons = document.querySelectorAll('[data-open-installments]');
        const modalTitle = document.getElementById('loan-modal-title');
        const modalSubtitle = document.getElementById('loan-modal-subtitle');
        const submitButton = document.getElementById('loan-submit-button');
        const loansTotal = document.getElementById('loans-total');
        const loansFiltered = document.getElementById('loans-filtered');
        const installmentsList = document.getElementById('installments-list');
        const installmentsSummary = document.getElementById('installments-summary');
        const installmentsModalTitle = document.getElementById('installments-modal-title');
        const installmentsModalSubtitle = document.getElementById('installments-modal-subtitle');
        const receiveModalTitle = document.getElementById('receive-modal-title');
        const receiveModalSubtitle = document.getElementById('receive-modal-subtitle');
        const receiveSummary = document.getElementById('receive-summary');
        const receiveDate = document.getElementById('receive-date');
        const receiveAmount = document.getElementById('receive-amount');
        const receiveOnlyInterest = document.getElementById('receive-only-interest');
        const receiveSubmitButton = document.getElementById('receive-submit-button');
        let selectedReceiveRow = null;
        let selectedInstallmentId = '';
        let currentLoanRow = null;
        let currentLoanId = '';
        let isSubmittingLoan = false;
        let isReceivingInstallment = false;

        const fields = {
            cliente: document.getElementById('cliente'),
            data_emprestimo: document.getElementById('data_emprestimo'),
            data_emprestimo_display: document.getElementById('data_emprestimo_display'),
            valor_emprestimo: document.getElementById('valor_emprestimo'),
            taxa_juros: document.getElementById('taxa_juros'),
            tipo_juros: document.getElementById('tipo_juros'),
            numero_parcelas: document.getElementById('numero_parcelas'),
            intervalo: document.getElementById('intervalo'),
            tipo_multa: document.getElementById('tipo_multa'),
            valor_multa: document.getElementById('valor_multa'),
            cobranca_multa: document.getElementById('cobranca_multa'),
            cobrador: document.getElementById('cobrador'),
            observacoes: document.getElementById('observacoes'),
        };

        const exceptionFields = {
            anular_sabados: document.getElementById('anular_sabados'),
            anular_domingos: document.getElementById('anular_domingos'),
            anular_feriados: document.getElementById('anular_feriados'),
        };

        if (!modal || !openButton || !form) {
            return;
        }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';

        const showToast = (message, type = 'success') => {
            const toast = document.createElement('div');
            toast.className = `toast${type === 'danger' ? ' toast-danger' : ''}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            window.setTimeout(() => toast.classList.add('is-visible'), 20);
            window.setTimeout(() => {
                toast.classList.remove('is-visible');
                window.setTimeout(() => toast.remove(), 250);
            }, 2200);
        };

        const apiRequest = async (url, options = {}) => {
            const headers = new Headers(options.headers || {});
            if (!headers.has('Accept')) {
                headers.set('Accept', 'application/json');
            }
            if (!headers.has('X-CSRF-TOKEN') && csrfToken) {
                headers.set('X-CSRF-TOKEN', csrfToken);
            }
            if (options.body && !headers.has('Content-Type')) {
                headers.set('Content-Type', 'application/json');
            }

            const response = await fetch(url, {
                credentials: 'same-origin',
                ...options,
                headers,
            });

            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json') ? await response.json().catch(() => null) : null;

            if (!response.ok) {
                const message = payload && payload.message ? payload.message : 'Não foi possível concluir a solicitação.';
                throw new Error(message);
            }

            return payload;
        };

        const bindTap = (element, handler) => {
            if (!(element instanceof Element)) {
                return;
            }

            let ignoreClick = false;

            element.addEventListener('pointerup', (event) => {
                if (!event || typeof event.pointerType !== 'string') {
                    return;
                }

                if (event.pointerType !== 'touch' && event.pointerType !== 'pen') {
                    return;
                }

                ignoreClick = true;
                window.setTimeout(() => {
                    ignoreClick = false;
                }, 350);

                event.preventDefault();
                handler(event);
            });

            element.addEventListener('click', (event) => {
                if (ignoreClick) {
                    return;
                }
                handler(event);
            });
        };

        const setupCobradorAutocomplete = (input) => {
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const field = input.closest('.field');
            if (field) {
                field.classList.add('has-autocomplete');
            }

            const menuId = `${input.id}-suggestions`;
            let menu = document.getElementById(menuId);
            if (!menu) {
                menu = document.createElement('div');
                menu.className = 'autocomplete-menu';
                menu.id = menuId;
                menu.setAttribute('aria-hidden', 'true');
                input.insertAdjacentElement('afterend', menu);
            }

            let debounceTimer = 0;
            let activeController = null;

            const closeMenu = () => {
                menu.classList.remove('is-open');
                menu.setAttribute('aria-hidden', 'true');
                menu.innerHTML = '';
            };

            const openMenu = () => {
                menu.classList.add('is-open');
                menu.setAttribute('aria-hidden', 'false');
            };

            const render = (items) => {
                menu.innerHTML = '';

                if (!Array.isArray(items) || items.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'autocomplete-empty';
                    empty.textContent = 'Nenhum cobrador encontrado.';
                    menu.appendChild(empty);
                    openMenu();
                    return;
                }

                items.forEach((item) => {
                    const name = item && typeof item.name === 'string' ? item.name.trim() : '';
                    if (!name) {
                        return;
                    }

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'autocomplete-option';
                    btn.textContent = name;
                    btn.dataset.value = name;
                    bindTap(btn, (event) => {
                        if (event && typeof event.preventDefault === 'function') {
                            event.preventDefault();
                        }
                        input.value = name;
                        closeMenu();
                        try {
                            input.focus({ preventScroll: true });
                        } catch (error) {
                            input.focus();
                        }
                    });
                    menu.appendChild(btn);
                });

                openMenu();
            };

            const fetchOptions = async (term) => {
                if (activeController) {
                    activeController.abort();
                }

                const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
                activeController = controller;

                const query = term ? `?q=${encodeURIComponent(term)}` : '';
                const url = `/admin/api/cobradores${query}`;

                try {
                    const payload = await apiRequest(url, controller ? { method: 'GET', signal: controller.signal } : { method: 'GET' });
                    const list = payload && Array.isArray(payload.data) ? payload.data : [];
                    render(list);
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    closeMenu();
                }
            };

            const scheduleFetch = () => {
                const term = input.value.trim();
                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                }
                debounceTimer = window.setTimeout(() => fetchOptions(term), 180);
            };

            input.addEventListener('input', scheduleFetch);
            input.addEventListener('focus', () => fetchOptions(input.value.trim()));
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });

            input.addEventListener('blur', () => {
                window.setTimeout(() => {
                    const active = document.activeElement;
                    if (active && menu.contains(active)) {
                        return;
                    }
                    closeMenu();
                }, 140);
            });

            document.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                if (!target) {
                    return;
                }

                if (target === input || menu.contains(target) || (field && field.contains(target))) {
                    return;
                }

                closeMenu();
            });
        };

        setupCobradorAutocomplete(fields.cobrador);
        setupCobradorAutocomplete(document.getElementById('cobrador_filtro'));

        const updateLoanCounters = () => {
            const rows = document.querySelectorAll('[data-loan-row]').length;

            if (loansTotal) {
                loansTotal.textContent = rows.toString();
            }

            if (loansFiltered) {
                loansFiltered.textContent = rows.toString();
            }
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

        const syncLoanDateFields = () => {
            if (!fields.data_emprestimo || !fields.data_emprestimo_display) {
                return;
            }

            const normalizedDisplay = normalizeDisplayDate(fields.data_emprestimo_display.value);
            if (normalizedDisplay !== fields.data_emprestimo_display.value) {
                fields.data_emprestimo_display.value = normalizedDisplay;
            }

            const iso = displayToIsoDate(normalizedDisplay);
            if (iso) {
                fields.data_emprestimo.value = iso;
            }
        };

        const setCreateMode = () => {
            if (modalTitle) {
                modalTitle.textContent = 'Novo emprestimo';
            }

            if (modalSubtitle) {
                modalSubtitle.textContent = 'Preencha os dados principais para iniciar um novo emprestimo.';
            }

            if (submitButton) {
                submitButton.textContent = 'Salvar emprestimo';
            }

            form.dataset.mode = 'create';
            delete form.dataset.editingLoan;
            delete form.dataset.loanId;
            form.reset();

            if (fields.data_emprestimo) {
                const todayIso = getTodayInputValue();
                fields.data_emprestimo.value = todayIso;
                if (fields.data_emprestimo_display) {
                    fields.data_emprestimo_display.value = isoToDisplayDate(todayIso);
                }
            }

            Object.values(exceptionFields).forEach((input) => {
                if (input) {
                    input.checked = false;
                }
            });

            if (fields.tipo_multa) {
                fields.tipo_multa.value = 'fixa';
                fields.tipo_multa.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        const setEditMode = (row) => {
            if (modalTitle) {
                modalTitle.textContent = 'Editar emprestimo';
            }

            if (modalSubtitle) {
                modalSubtitle.textContent = 'Atualize as informacoes do emprestimo selecionado.';
            }

            if (submitButton) {
                submitButton.textContent = 'Salvar alteracoes';
            }

            form.dataset.mode = 'edit';
            form.dataset.loanId = row.dataset.loanId || '';

            let loan = null;
            try {
                loan = row.dataset.loan ? JSON.parse(row.dataset.loan) : null;
            } catch (error) {
                loan = null;
            }

            const L = (key, fallback = '') => (loan && typeof loan[key] !== 'undefined' ? loan[key] : fallback);

            const desiredCliente = String(L('cliente', '')).trim();
            fields.cliente.value = desiredCliente;
            if (desiredCliente && fields.cliente.value !== desiredCliente) {
                const option = new Option(desiredCliente, desiredCliente, true, true);
                const insertBefore = fields.cliente.options[1] || null;
                fields.cliente.insertBefore(option, insertBefore);
                fields.cliente.value = desiredCliente;
            }
            fields.cliente.dispatchEvent(new Event('change', { bubbles: true }));
            fields.data_emprestimo.value = String(L('data_emprestimo', ''));
            if (fields.data_emprestimo_display) {
                fields.data_emprestimo_display.value = isoToDisplayDate(fields.data_emprestimo.value);
            }
            fields.valor_emprestimo.value = String(L('valor', ''));
            fields.taxa_juros.value = String(L('taxa_juros', ''));
            fields.tipo_juros.value = String(L('tipo_juros', 'simples'));
            fields.numero_parcelas.value = L('numero_parcelas', '') === '' ? '' : String(L('numero_parcelas', ''));
            fields.intervalo.value = String(L('intervalo', 'mensal'));
            fields.tipo_multa.value = String(L('tipo_multa', 'percentual'));
            fields.valor_multa.value = String(L('valor_multa', ''));
            fields.cobranca_multa.value = String(L('cobranca_multa', 'automatica'));
            fields.cobrador.value = String(L('cobrador', ''));
            [fields.tipo_juros, fields.intervalo, fields.tipo_multa, fields.cobranca_multa].forEach((select) => {
                if (select) {
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            const loanType = String(L('tipo', ''));
            fields.observacoes.value = loanType ? `Emprestimo do tipo ${loanType}.` : '';

            const selectedExceptions = Array.isArray(L('excecoes_dia', [])) ? L('excecoes_dia', []) : String(L('excecoes_dia', '')).split(',').filter(Boolean);

            Object.entries(exceptionFields).forEach(([key, input]) => {
                if (input) {
                    input.checked = selectedExceptions.includes(key);
                }
            });
        };

        const openModal = () => {
            modal.classList.add('active');
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.remove('active');
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-overlay.active, .modal-overlay.is-open')) {
                document.body.style.overflow = '';
            }
        };

        const openInstallmentsModal = () => {
            if (!installmentsModal) {
                return;
            }

            installmentsModal.classList.add('active');
            installmentsModal.classList.add('is-open');
            installmentsModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeInstallmentsModal = () => {
            if (!installmentsModal) {
                return;
            }

            installmentsModal.classList.remove('active');
            installmentsModal.classList.remove('is-open');
            installmentsModal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-overlay.active, .modal-overlay.is-open')) {
                document.body.style.overflow = '';
            }
        };

        const openReceiveModal = () => {
            if (!receiveModal) {
                return;
            }

            receiveModal.classList.add('active');
            receiveModal.classList.add('is-open');
            receiveModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeReceiveModal = () => {
            if (!receiveModal) {
                return;
            }

            receiveModal.classList.remove('active');
            receiveModal.classList.remove('is-open');
            receiveModal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-overlay.active, .modal-overlay.is-open')) {
                document.body.style.overflow = '';
            }
            if (receiveOnlyInterest) {
                receiveOnlyInterest.checked = false;
            }
            selectedReceiveRow = null;
            selectedInstallmentId = '';
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

            const normalizeDigits = (digits) => String(digits || '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');

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

            setRawDigits(input.dataset.rawDigits || input.value || '');

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
                setRawDigits(text);
                event.preventDefault();
            });

            input.addEventListener('blur', updateDisplay);
        };

        const receiveButtonContent = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M4 7h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16 3v4M8 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 14l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Receber</span>';

        bindCurrencyMask(fields.valor_emprestimo);
        bindCurrencyMask(receiveAmount);

        const formatCurrencyFromCents = (cents) => formatCurrencyValue((Number(cents) || 0) / 100);

        const getLoanStatusLabel = (status) => {
            switch (status) {
            case 'em_dia':
                return 'Em dia';
            case 'atrasado':
                return 'Atrasado';
            case 'analise':
                return 'Em análise';
            case 'quitado':
                return 'Quitado';
            default:
                return String(status || '').replace(/_/g, ' ');
            }
        };

        const setLoanJson = (row, loan) => {
            const payload = {
                id: String(loan.id || row.dataset.loanId || ''),
                cliente: String(loan.cliente || ''),
                valor: String(loan.valor || ''),
                parcelas: String(loan.parcelas || ''),
                numero_parcelas: loan.numero_parcelas === null || loan.numero_parcelas === undefined ? '' : loan.numero_parcelas,
                vencimento: String(loan.vencimento || ''),
                vencimento_display: String(loan.vencimento_display || isoToDisplayDate(loan.vencimento || '') || ''),
                tipo: String(loan.tipo || ''),
                status: String(loan.status || ''),
                data_emprestimo: String(loan.data_emprestimo || ''),
                taxa_juros: String(loan.taxa_juros || ''),
                tipo_juros: String(loan.tipo_juros || 'simples'),
                intervalo: String(loan.intervalo || 'mensal'),
                tipo_multa: String(loan.tipo_multa || 'percentual'),
                valor_multa: String(loan.valor_multa || ''),
                cobranca_multa: String(loan.cobranca_multa || 'automatica'),
                cobrador: String(loan.cobrador || ''),
                excecoes_dia: Array.isArray(loan.excecoes_dia) ? loan.excecoes_dia : String(loan.excecoes_dia || '').split(',').filter(Boolean),
            };
            row.dataset.loanId = payload.id;
            try {
                row.dataset.loan = JSON.stringify(payload);
            } catch (error) {
                row.dataset.loan = '{}';
            }
        };

        const applyLoanToRow = (row, loan) => {
            setLoanJson(row, loan);

            const cells = row.querySelectorAll('td');
            if (cells.length < 7) {
                return;
            }

            // === CELL 0 (CLIENTE): Preserva estrutura AVATAR + NOME (não apaga o flex do avatar!) ===
            const clienteNameEl = cells[0].querySelector('.client-name, .kcliente-nome, .kcliente-nome-emprestimo');
            const clienteAvatarEl = cells[0].querySelector('.client-avatar, .kavatar, .kavatar-emprestimo, .kavatar-cliente');
            const clienteValue = loan.cliente || '';

            if (clienteNameEl) {
                // Caminho FELIZ: estrutura existe, atualiza só textos
                clienteNameEl.textContent = clienteValue;
                if (clienteAvatarEl && clienteValue) {
                    // Atualiza iniciais do avatar (cálculo idêntico ao Blade)
                    const partesNome = clienteValue.trim().split(/\s+/).filter(Boolean);
                    const primeiraLetra = partesNome[0] ? partesNome[0].charAt(0).toUpperCase() : '?';
                    const ultimaLetra = partesNome.length > 1 ? partesNome[partesNome.length - 1].charAt(0).toUpperCase() : '';
                    clienteAvatarEl.textContent = primeiraLetra + ultimaLetra;
                }
            } else {
                // Fallback: estrutura não existe, recria do zero igual ao Blade
                const partesNomeFb = clienteValue.trim().split(/\s+/).filter(Boolean);
                const primeiraLetraFb = partesNomeFb[0] ? partesNomeFb[0].charAt(0).toUpperCase() : '?';
                const ultimaLetraFb = partesNomeFb.length > 1 ? partesNomeFb[partesNomeFb.length - 1].charAt(0).toUpperCase() : '';
                const iniciaisFb = primeiraLetraFb + ultimaLetraFb;
                const palette = ['kavatar-indigo', 'kavatar-emerald', 'kavatar-amber', 'kavatar-purple', 'kavatar-sky', 'kavatar-rose'];
                let colorIdx = 0;
                try {
                    const rowIdxAttr = row.dataset && row.dataset.loanId ? row.dataset.loanId : clienteValue;
                    for (let i = 0; i < rowIdxAttr.length; i++) colorIdx = (colorIdx + rowIdxAttr.charCodeAt(i)) % palette.length;
                } catch (e) { /* noop */ }
                const avatarClassFb = palette[colorIdx] || 'kavatar-indigo';

                cells[0].innerHTML = `
                    <div class="kcell-cliente kcell-emprestimo-cliente client-cell">
                        <span class="kavatar kavatar-cliente kavatar-emprestimo client-avatar ${avatarClassFb}">${iniciaisFb}</span>
                        <div class="kcell-cliente-meta kcell-emprestimo-meta client-cell-meta">
                            <div class="kcliente-nome kcliente-nome-emprestimo client-name">${clienteValue}</div>
                        </div>
                    </div>
                `;
            }

            cells[1].textContent = loan.valor || '';
            cells[2].textContent = loan.parcelas || '';
            cells[3].textContent = loan.vencimento_display || isoToDisplayDate(loan.vencimento || '') || '';
            const typeBadge = cells[4].querySelector('.type-badge, .ktype-badge, .ktype-badge-emprestimo');
            if (typeBadge) {
                typeBadge.textContent = loan.tipo || '';
            } else {
                cells[4].innerHTML = `<span class="ktype-badge ktype-badge-emprestimo type-badge">${loan.tipo || ''}</span>`;
            }

            const statusLabel = getLoanStatusLabel(loan.status);
            const statusClass = loan.status || 'em_dia';
            const statusPill = cells[5].querySelector('.status-badge, .kstatus-pill, .kstatus-pill-emprestimo');
            const statusDot = cells[5].querySelector('.status-dot, .kstatus-dot, .kstatus-dot-' + statusClass);
            const statusClassesLegadas = `kstatus-pill kstatus-pill-emprestimo kstatus-pill-${statusClass} status-badge status-${statusClass}`;
            const dotClassesLegadas = `kstatus-dot kstatus-dot-${statusClass} status-dot`;

            if (statusPill) {
                statusPill.className = statusClassesLegadas;
                statusPill.childNodes.forEach((n) => { if (n.nodeType === Node.TEXT_NODE) n.remove(); });
                let dot = statusPill.querySelector('.status-dot, .kstatus-dot');
                if (!dot) {
                    dot = document.createElement('span');
                    statusPill.insertBefore(dot, statusPill.firstChild);
                }
                dot.className = dotClassesLegadas;
                dot.setAttribute('aria-hidden', 'true');
                const labelNode = document.createTextNode(' ' + statusLabel);
                statusPill.appendChild(labelNode);
            } else {
                cells[5].innerHTML = `
                    <span class="${statusClassesLegadas}">
                        <span class="${dotClassesLegadas}" aria-hidden="true"></span> ${statusLabel}
                    </span>
                `;
            }
        };

        const bindLoanRowActions = (row) => {
            if (!row || row.dataset.boundActions === '1') {
                return;
            }
            row.dataset.boundActions = '1';

            let loan = null;
            try {
                loan = row.dataset.loan ? JSON.parse(row.dataset.loan) : null;
            } catch (error) {
                loan = null;
            }

            const editBtn = row.querySelector('[data-edit-loan]');
            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    setEditMode(row);
                    openModal();
                });
            }

            const deleteBtn = row.querySelector('[data-delete-loan]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    const loanId = row.dataset.loanId || '';
                    if (!loanId) {
                        return;
                    }
                    // —— SUBSTITUI window.confirm PELO MODAL CUSTOMIZADO PREMIUM ——
                    // O window.confirm() foi REMOVIDO para não violar a regra
                    // "Proibido usar window.confirm: utilizar modal de confirmação
                    // customizado do projeto." (conforme Project Memory).
                    openDeleteLoanModal(row, deleteBtn);
                });
            }

            const installmentsBtn = row.querySelector('[data-open-installments]');
            if (installmentsBtn) {
                installmentsBtn.addEventListener('click', () => {
                    const loanId = row.dataset.loanId || '';
                    if (!loanId) {
                        return;
                    }
                    currentLoanRow = row;
                    currentLoanId = loanId;
                    openInstallmentsModal();
                    loadInstallmentsForLoan(loanId, row);
                });
            }
        };

        const createLoanRowElement = (loan) => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-loan-row', '');
            tr.innerHTML = `
                <td class="font-medium"></td>
                <td class="text-amount"></td>
                <td></td>
                <td></td>
                <td><span class="type-badge"></span></td>
                <td></td>
                <td>
                    <div class="table-actions">
                        <button type="button" class="icon-btn" data-edit-loan title="Editar emprestimo" aria-label="Editar emprestimo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button type="button" class="icon-btn icon-btn-danger" data-delete-loan title="Excluir emprestimo" aria-label="Excluir emprestimo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </button>
                        <button type="button" class="icon-btn icon-btn-primary" data-open-installments title="Acessar parcelas" aria-label="Acessar parcelas">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M3 10h18M8 2v4M16 2v4M8 14h3M13 14h3M8 18h3"/>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            applyLoanToRow(tr, loan);
            bindLoanRowActions(tr);
            return tr;
        };

        const upsertLoanRow = (loan) => {
            const loanId = loan.id || '';
            if (!loanId) {
                return;
            }

            const existing = document.querySelector(`[data-loan-row][data-loan-id="${loanId}"]`);
            if (existing) {
                applyLoanToRow(existing, loan);
                bindLoanRowActions(existing);
                return;
            }

            let tbody = document.querySelector('.projects-table tbody');
            if (!tbody) {
                const emptyState = document.querySelector('.page-admin-emprestimos .empty-state');
                if (emptyState) {
                    emptyState.remove();
                }

                const card = document.querySelector('.page-admin-emprestimos section.card');
                const meta = document.querySelector('.page-admin-emprestimos .table-meta');
                if (card && meta) {
                    const wrap = document.createElement('div');
                    wrap.className = 'table-wrap';
                    wrap.innerHTML = `
                        <table class="projects-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Parcelas</th>
                                    <th>Vencimento</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    `;
                    meta.insertAdjacentElement('afterend', wrap);
                    tbody = wrap.querySelector('tbody');
                } else {
                    window.location.reload();
                    return;
                }
            }

            tbody.prepend(createLoanRowElement(loan));
            updateLoanCounters();
        };

        const loadInstallmentsForLoan = async (loanId, row) => {
            if (!installmentsList) {
                return;
            }

            installmentsList.innerHTML = '<tr><td colspan="8">Carregando parcelas...</td></tr>';

            let rowLoan = null;
            try {
                rowLoan = row && row.dataset && row.dataset.loan ? JSON.parse(row.dataset.loan) : null;
            } catch (error) {
                rowLoan = null;
            }

            try {
                let payload = await apiRequest(`/admin/api/emprestimos/${loanId}/parcelas`, { method: 'GET' });
                if (payload && payload.needs_repair) {
                    await apiRequest(`/admin/api/emprestimos/${loanId}/parcelas/sync`, { method: 'POST' });
                    payload = await apiRequest(`/admin/api/emprestimos/${loanId}/parcelas`, { method: 'GET' });
                }
                const loan = payload && payload.loan ? payload.loan : null;
                const installments = payload && Array.isArray(payload.installments) ? payload.installments : [];

                if (installmentsModalTitle) {
                    installmentsModalTitle.textContent = 'Cronograma de Parcelas';
                }
                if (installmentsModalSubtitle) {
                    const rowCliente = rowLoan && rowLoan.cliente ? rowLoan.cliente : '';
                    const loanCliente = loan && loan.cliente ? loan.cliente : '';
                    const clienteLabel = rowCliente || loanCliente || 'Cliente';
                    const rowIntervalo = rowLoan && rowLoan.intervalo ? rowLoan.intervalo : '';
                    const loanIntervalo = loan && loan.intervalo ? loan.intervalo : '';
                    const intervaloLabel = (loanIntervalo || rowIntervalo || 'mensal').toUpperCase();
                    installmentsModalSubtitle.textContent = `${clienteLabel} - parcelas ${intervaloLabel}.`;
                }

                const settledStatuses = ['pago', 'pago_parcial'];
                const principalReceived = installments.filter((i) => settledStatuses.includes(i.status)).reduce((sum, i) => sum + (Number(i.amortizacao_cents) || 0), 0);
                const interestReceived = installments.filter((i) => settledStatuses.includes(i.status)).reduce((sum, i) => sum + (Number(i.juros_cents) || 0), 0);
                const interestToReceive = installments.filter((i) => !settledStatuses.includes(i.status)).reduce((sum, i) => sum + (Number(i.juros_cents) || 0), 0);
                const overdueTotal = installments.filter((i) => i.status === 'vencida').reduce((sum, i) => sum + (Number(i.total_cents) || 0), 0);
                const currentDebt = installments.filter((i) => !settledStatuses.includes(i.status)).reduce((sum, i) => sum + (Number(i.total_cents) || 0), 0);

                if (installmentsSummary) {
                    installmentsSummary.innerHTML = `
                        <div class="summary-item"><span>Receita Principal</span><strong>${formatCurrencyFromCents(principalReceived)}</strong></div>
                        <div class="summary-item"><span>Receita Juros</span><strong>${formatCurrencyFromCents(interestReceived)}</strong></div>
                        <div class="summary-item"><span>Juros a Receber</span><strong>${formatCurrencyFromCents(interestToReceive)}</strong></div>
                        <div class="summary-item text-red"><span>Atrasado</span><strong>${formatCurrencyFromCents(overdueTotal)}</strong></div>
                        <div class="summary-item text-green"><span>Saldo Devedor</span><strong>${formatCurrencyFromCents(currentDebt)}</strong></div>
                    `;
                }

                installmentsList.innerHTML = '';

                if (installments.length === 0) {
                    installmentsList.innerHTML = '<tr><td colspan="8">Ainda nao ha parcelas disponiveis para este emprestimo.</td></tr>';
                    return;
                }

                installments.forEach((item) => {
                    const status = item.status === 'pago'
                        ? 'Pago'
                        : item.status === 'pago_parcial'
                            ? 'Pago parcial'
                            : item.status === 'vencida'
                                ? 'Vencida'
                                : 'A vencer';
                    const statusClass = item.status === 'pago'
                        ? 'recebida'
                        : item.status === 'pago_parcial'
                            ? 'parcial'
                            : item.status === 'vencida'
                                ? 'vencida'
                                : 'a-vencer';
                    const canReceive = !['pago', 'pago_parcial'].includes(item.status) && Boolean(item.id);

                    const line = document.createElement('tr');
                    line.dataset.installmentId = item.id || '';
                    line.innerHTML = `
                        <td>${item.numero === null || item.numero === undefined ? '' : item.numero}</td>
                        <td>${item.vencimento_display || isoToDisplayDate(item.vencimento || '') || ''}</td>
                        <td>${item.amortizacao || ''}</td>
                        <td>${item.juros || ''}</td>
                        <td>${item.multa || ''}</td>
                        <td><strong>${item.total || ''}</strong></td>
                        <td><span class="status-badge status-${statusClass}"><span class="status-dot" aria-hidden="true"></span> ${status}</span></td>
                        <td class="text-right">
                            ${canReceive ? `<button type="button" class="action-table-btn" data-trigger-receive data-parcela-id="${item.id}" data-installment="${item.numero === null || item.numero === undefined ? '' : item.numero}" data-amortization-cents="${item.amortizacao_cents === null || item.amortizacao_cents === undefined ? 0 : item.amortizacao_cents}" data-interest-cents="${item.juros_cents === null || item.juros_cents === undefined ? 0 : item.juros_cents}" data-total-cents="${item.total_cents === null || item.total_cents === undefined ? 0 : item.total_cents}">${receiveButtonContent}</button>` : '<span>-</span>'}
                        </td>
                    `;
                    installmentsList.appendChild(line);
                });
            } catch (error) {
                installmentsList.innerHTML = '<tr><td colspan="8">Erro ao carregar parcelas.</td></tr>';
                showToast(error instanceof Error ? error.message : 'Erro ao carregar parcelas.', 'danger');
            }
        };

        openButton.addEventListener('click', () => {
            setCreateMode();
            openModal();
        });

        if (fields.data_emprestimo_display) {
            fields.data_emprestimo_display.addEventListener('input', syncLoanDateFields);
            fields.data_emprestimo_display.addEventListener('blur', syncLoanDateFields);
            fields.data_emprestimo_display.addEventListener('paste', () => window.setTimeout(syncLoanDateFields, 0));
        }

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        closeInstallmentsButtons.forEach((button) => {
            button.addEventListener('click', closeInstallmentsModal);
        });

        closeReceiveButtons.forEach((button) => {
            button.addEventListener('click', closeReceiveModal);
        });

        document.querySelectorAll('[data-loan-row]').forEach((row) => bindLoanRowActions(row));

        if (installmentsList) {
            installmentsList.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const actionButton = target ? target.closest('.action-table-btn') : null;

                if (!actionButton) {
                    return;
                }

                selectedReceiveRow = actionButton.closest('tr');
                selectedInstallmentId = actionButton.dataset.parcelaId || '';

                const installmentNumber = actionButton.dataset.installment || '';
                const amortizationCents = Number.parseInt(actionButton.dataset.amortizationCents || '0', 10) || 0;
                const interestCents = Number.parseInt(actionButton.dataset.interestCents || '0', 10) || 0;
                const totalCents = Number.parseInt(actionButton.dataset.totalCents || '0', 10) || 0;

                if (receiveModalTitle) {
                    receiveModalTitle.textContent = `Registrar Recebimento - Parcela ${installmentNumber}`;
                }

                if (receiveModalSubtitle) {
                    receiveModalSubtitle.textContent = 'Revise os valores e registre o recebimento.';
                }

                if (receiveSummary) {
                    receiveSummary.innerHTML = `
                        <div class="receive-row"><span>Principal</span><strong>${formatCurrencyFromCents(amortizationCents)}</strong></div>
                        <div class="receive-row"><span>Juros</span><strong>${formatCurrencyFromCents(interestCents)}</strong></div>
                        <div class="receive-divider"></div>
                        <div class="receive-row receive-total"><span>Total a Receber</span><strong>${formatCurrencyFromCents(totalCents)}</strong></div>
                    `;
                }

                if (receiveDate) {
                    receiveDate.value = new Date().toISOString().slice(0, 10);
                }

                if (receiveAmount) {
                    receiveAmount.dataset.totalCents = String(totalCents);
                    receiveAmount.dataset.interestCents = String(interestCents);
                    receiveAmount.value = formatCurrencyFromCents(totalCents);
                }

                if (receiveOnlyInterest) {
                    receiveOnlyInterest.checked = false;
                }

                openReceiveModal();
            });
        }

        if (receiveOnlyInterest) {
            receiveOnlyInterest.addEventListener('change', () => {
                if (!receiveAmount) {
                    return;
                }

                const totalCents = Number.parseInt(receiveAmount.dataset.totalCents || '0', 10) || 0;
                const interestCents = Number.parseInt(receiveAmount.dataset.interestCents || '0', 10) || 0;
                receiveAmount.value = formatCurrencyFromCents(receiveOnlyInterest.checked ? interestCents : totalCents);
            });
        }

        if (receiveSubmitButton) {
            receiveSubmitButton.addEventListener('click', async () => {
                if (!selectedReceiveRow || !selectedInstallmentId || !receiveDate) {
                    return;
                }
                if (isReceivingInstallment) {
                    return;
                }

                isReceivingInstallment = true;
                if (receiveSubmitButton instanceof HTMLButtonElement) {
                    receiveSubmitButton.disabled = true;
                }

                try {
                    await apiRequest(`/admin/api/parcelas/${selectedInstallmentId}/receber`, {
                        method: 'POST',
                        body: JSON.stringify({
                            receive_date: receiveDate.value,
                            receive_amount: receiveAmount ? receiveAmount.value : '',
                            only_interest: Boolean(receiveOnlyInterest && receiveOnlyInterest.checked),
                        }),
                    });

                    showToast('Recebimento registrado com sucesso.');
                    closeReceiveModal();

                    if (currentLoanId) {
                        await loadInstallmentsForLoan(currentLoanId, currentLoanRow);
                        const loanPayload = await apiRequest(`/admin/api/emprestimos/${currentLoanId}`, { method: 'GET' });
                        if (loanPayload && loanPayload.data && currentLoanRow) {
                            upsertLoanRow(loanPayload.data);
                        }
                    }
                } catch (error) {
                    showToast(error instanceof Error ? error.message : 'Erro ao registrar recebimento.', 'danger');
                } finally {
                    isReceivingInstallment = false;
                    if (receiveSubmitButton instanceof HTMLButtonElement) {
                        receiveSubmitButton.disabled = false;
                    }
                }
            });
        }

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        if (installmentsModal) {
            installmentsModal.addEventListener('click', (event) => {
                if (event.target === installmentsModal) {
                    closeInstallmentsModal();
                }
            });
        }

        if (receiveModal) {
            receiveModal.addEventListener('click', (event) => {
                if (event.target === receiveModal) {
                    closeReceiveModal();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (receiveModal && receiveModal.classList.contains('active')) {
                closeReceiveModal();
                return;
            }

            if (installmentsModal && installmentsModal.classList.contains('active')) {
                closeInstallmentsModal();
                return;
            }

            if (modal.classList.contains('active')) {
                closeModal();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (isSubmittingLoan) {
                return;
            }

            syncLoanDateFields();
            const mode = form.dataset.mode || 'create';
            const loanId = form.dataset.loanId || '';

            const payload = {
                cliente: fields.cliente.value,
                data_emprestimo: fields.data_emprestimo.value,
                valor_emprestimo: fields.valor_emprestimo.value,
                taxa_juros: fields.taxa_juros.value,
                tipo_juros: fields.tipo_juros.value,
                numero_parcelas: Number.parseInt(fields.numero_parcelas.value || '0', 10) || 0,
                intervalo: fields.intervalo.value,
                tipo_multa: fields.tipo_multa.value,
                valor_multa: fields.valor_multa.value,
                cobranca_multa: fields.cobranca_multa.value,
                cobrador: fields.cobrador.value.trim(),
                excecoes_dia: Object.entries(exceptionFields)
                    .filter(([, input]) => input && input.checked)
                    .map(([key]) => key),
                observacoes: fields.observacoes.value,
            };

            const originalSubmitLabel = submitButton ? submitButton.textContent : '';
            isSubmittingLoan = true;
            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = true;
                submitButton.textContent = 'Salvando...';
            }

            try {
                const endpoint = mode === 'edit' && loanId ? `/admin/api/emprestimos/${loanId}` : '/admin/api/emprestimos';
                const method = mode === 'edit' && loanId ? 'PATCH' : 'POST';

                const result = await apiRequest(endpoint, {
                    method,
                    body: JSON.stringify(payload),
                });

                const savedId = result && result.id ? result.id : loanId;
                if (savedId) {
                    const loanPayload = await apiRequest(`/admin/api/emprestimos/${savedId}`, { method: 'GET' });
                    if (loanPayload && loanPayload.data) {
                        upsertLoanRow(loanPayload.data);
                    }
                }

                showToast(mode === 'edit' ? 'Empréstimo atualizado com sucesso.' : 'Empréstimo cadastrado com sucesso.');
                closeModal();
                setCreateMode();
            } catch (error) {
                showToast(error instanceof Error ? error.message : 'Erro ao salvar empréstimo.', 'danger');
            } finally {
                isSubmittingLoan = false;
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalSubmitLabel;
                }
            }
        });

        setCreateMode();
    })();
});
