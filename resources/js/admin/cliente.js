document.addEventListener('DOMContentLoaded', () => {
    if (window.__keneddyClientesInitialized) {
        return;
    }
    window.__keneddyClientesInitialized = true;

    const modal = document.getElementById('client-modal');
    const deleteModal = document.getElementById('client-delete-modal');
    const openButton = document.querySelector('[data-open-client-modal]');
    const closeButtons = document.querySelectorAll('[data-close-client-modal]');
    const closeDeleteButtons = document.querySelectorAll('[data-close-delete-modal]');
    const confirmDeleteButton = document.getElementById('confirm-delete-client');
    const deleteClientNameEl = document.querySelector('.kdelete-client-name');
    const form = document.getElementById('client-form');
    const modalTitle = document.getElementById('client-modal-title');
    const modalSubtitle = document.getElementById('client-modal-subtitle');
    const submitButton = document.getElementById('client-submit-button');

    let pendingDeleteId = null;

    const fields = {
        nome: document.getElementById('nome'),
        telefone: document.getElementById('telefone'),
        cpf: document.getElementById('cpf'),
        endereco: document.getElementById('endereco'),
        cidade: document.getElementById('cidade'),
        chave_pix: document.getElementById('chave_pix'),
        banco: document.getElementById('banco'),
    };

    if (!modal || !openButton || !form) {
        return;
    }

    const csrfToken = (() => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    })();

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

    const toTitleCase = (value) => {
        const normalized = String(value || '').replace(/\s+/g, ' ').trimStart();
        if (!normalized) {
            return '';
        }

        return normalized
            .split(' ')
            .map((word) => {
                const trimmed = word.trim();
                if (!trimmed) {
                    return '';
                }

                if (/^[^a-zA-ZÀ-ÿ]*$/.test(trimmed)) {
                    return trimmed;
                }

                const lower = trimmed.toLowerCase();
                return lower.charAt(0).toUpperCase() + lower.slice(1);
            })
            .join(' ');
    };

    const formatPhone = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
        if (!digits) {
            return '';
        }

        const ddd = digits.slice(0, 2);
        const rest = digits.slice(2);

        if (rest.length <= 4) {
            return `(${ddd}) ${rest}`;
        }

        if (rest.length <= 8) {
            return `(${ddd}) ${rest.slice(0, 4)}-${rest.slice(4)}`;
        }

        return `(${ddd}) ${rest.slice(0, 5)}-${rest.slice(5)}`;
    };

    const formatCpf = (value) => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
        if (!digits) {
            return '';
        }

        const p1 = digits.slice(0, 3);
        const p2 = digits.slice(3, 6);
        const p3 = digits.slice(6, 9);
        const p4 = digits.slice(9, 11);

        if (digits.length <= 3) {
            return p1;
        }

        if (digits.length <= 6) {
            return `${p1}.${p2}`;
        }

        if (digits.length <= 9) {
            return `${p1}.${p2}.${p3}`;
        }

        return `${p1}.${p2}.${p3}-${p4}`;
    };

    const bindTitleCase = (input) => {
        if (!input) {
            return;
        }

        const handler = () => {
            const formatted = toTitleCase(input.value);
            if (formatted !== input.value) {
                input.value = formatted;
            }
        };

        input.addEventListener('input', handler);
        input.addEventListener('blur', handler);
    };

    const bindMask = (input, formatter) => {
        if (!input) {
            return;
        }

        const handler = () => {
            const formatted = formatter(input.value);
            if (formatted !== input.value) {
                input.value = formatted;
            }
        };

        input.addEventListener('input', handler);
        input.addEventListener('blur', handler);
        input.addEventListener('paste', () => window.setTimeout(handler, 0));
    };

    bindTitleCase(fields.nome);
    bindTitleCase(fields.endereco);
    bindTitleCase(fields.cidade);
    bindTitleCase(fields.banco);
    bindMask(fields.telefone, formatPhone);
    bindMask(fields.cpf, formatCpf);

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

    const fillForm = (data) => {
        Object.entries(fields).forEach(([key, input]) => {
            if (input) {
                input.value = data[key] || '';
            }
        });
    };

    const setCreateMode = () => {
        if (modalTitle) {
            modalTitle.textContent = 'Cadastrar Novo Cliente';
        }

        if (modalSubtitle) {
            modalSubtitle.textContent = 'Insira as informações principais para registrar o cliente no sistema.';
        }

        if (submitButton) {
            submitButton.textContent = 'Salvar Cadastro';
        }

        form.dataset.mode = 'create';
        delete form.dataset.clientId;
        form.reset();
    };

    const getClientFromRow = (row) => {
        try {
            return row && row.dataset && row.dataset.client ? JSON.parse(row.dataset.client) : null;
        } catch {
            return null;
        }
    };

    const setEditMode = (row) => {
        if (modalTitle) {
            modalTitle.textContent = 'Editar cliente';
        }

        if (modalSubtitle) {
            modalSubtitle.textContent = 'Atualize os dados do cliente selecionado.';
        }

        if (submitButton) {
            submitButton.textContent = 'Salvar alterações';
        }

        form.dataset.mode = 'edit';
        form.dataset.clientId = row.dataset.clientId || '';

        const client = getClientFromRow(row);
        const C = (key, fallback = '') => (client && typeof client[key] !== 'undefined' ? client[key] : fallback);

        fillForm({
            nome: String(C('nome', '')),
            telefone: String(C('telefone', '')),
            cpf: String(C('cpf', '')),
            endereco: String(C('endereco', '')),
            cidade: String(C('cidade', '')),
            chave_pix: String(C('chave_pix', '')),
            banco: String(C('banco', '')),
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
        document.body.style.overflow = '';
    };

    const openDeleteModal = (clientName, id) => {
        if (deleteClientNameEl) {
            deleteClientNameEl.textContent = clientName;
        }
        pendingDeleteId = id;
        if (deleteModal) {
            deleteModal.classList.add('active');
            deleteModal.classList.add('is-open');
            deleteModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    };

    const closeDeleteModal = () => {
        pendingDeleteId = null;
        if (deleteModal) {
            deleteModal.classList.remove('active');
            deleteModal.classList.remove('is-open');
            deleteModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    };

    let isSubmitting = false;
    const setSubmittingState = (submitting) => {
        isSubmitting = submitting;
        if (submitButton) {
            submitButton.disabled = submitting;
            submitButton.setAttribute('aria-busy', submitting ? 'true' : 'false');
        }
    };

    openButton.addEventListener('click', () => {
        setCreateMode();
        openModal();
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    closeDeleteButtons.forEach((button) => {
        button.addEventListener('click', closeDeleteModal);
    });

    if (confirmDeleteButton) {
        confirmDeleteButton.addEventListener('click', async () => {
            const id = pendingDeleteId;
            if (!id) {
                closeDeleteModal();
                return;
            }

            try {
                confirmDeleteButton.disabled = true;
                await apiRequest(`/admin/api/clientes/${encodeURIComponent(id)}`, { method: 'DELETE' });
                closeDeleteModal();
                showToast('Cliente excluído com sucesso.');
                window.setTimeout(() => window.location.reload(), 400);
            } catch (error) {
                confirmDeleteButton.disabled = false;
                showToast(error.message, 'danger');
            }
        });
    }

    document.addEventListener('click', async (event) => {
        const target = event.target;
        const editButton = target.closest ? target.closest('[data-edit-client]') : null;
        if (editButton) {
            const row = editButton.closest('[data-client-row]');
            if (row) {
                setEditMode(row);
                openModal();
            }
            return;
        }

        const deleteButton = target.closest ? target.closest('[data-delete-client]') : null;
        if (deleteButton) {
            const row = deleteButton.closest('[data-client-row]');
            if (!row) {
                return;
            }

            const id = row.dataset.clientId || '';
            if (!id) {
                showToast('Cliente inválido para exclusão.', 'danger');
                return;
            }

            const client = getClientFromRow(row);
            const clientName = client && client.nome ? String(client.nome) : 'este cliente';
            openDeleteModal(clientName, id);
            return;
        }
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (deleteModal) {
        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (modal.classList.contains('active')) {
                closeModal();
            }
            if (deleteModal && deleteModal.classList.contains('active')) {
                closeDeleteModal();
            }
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        setSubmittingState(true);

        const payload = {
            _token: csrfToken,
            nome: fields.nome.value,
            telefone: fields.telefone.value,
            cpf: fields.cpf.value,
            endereco: fields.endereco.value,
            cidade: fields.cidade.value,
            chave_pix: fields.chave_pix.value,
            banco: fields.banco.value,
        };

        const mode = form.dataset.mode || 'create';

        try {
            if (mode === 'edit') {
                const id = form.dataset.clientId || '';
                if (!id) {
                    showToast('Cliente inválido para edição.', 'danger');
                    setSubmittingState(false);
                    return;
                }

                await apiRequest(`/admin/api/clientes/${encodeURIComponent(id)}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams(payload),
                });
            } else {
                await apiRequest('/admin/api/clientes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams(payload),
                });
            }

            closeModal();
            showToast('Cliente salvo com sucesso.');
            window.setTimeout(() => window.location.reload(), 400);
        } catch (error) {
            showToast(error.message, 'danger');
            setSubmittingState(false);
        }
    });

    setCreateMode();
});
