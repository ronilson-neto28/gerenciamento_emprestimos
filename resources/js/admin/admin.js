document.addEventListener('DOMContentLoaded', () => {
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

    const closeAllSelectMenus = (except) => {
        document.querySelectorAll('.select-ui.is-open').forEach((item) => {
            if (except && item === except) {
                return;
            }
            item.classList.remove('is-open');
        });
    };

    const buildSelectUi = (select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }

        if (select.dataset.enhanced === 'true') {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'select-ui';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'select-trigger';

        const ariaLabel = select.getAttribute('aria-label');
        if (ariaLabel) {
            trigger.setAttribute('aria-label', ariaLabel.trim());
        }

        const fieldWrapper = select.closest ? select.closest('.field') : null;
        const label = fieldWrapper ? fieldWrapper.querySelector(`label[for="${select.id}"]`) : null;
        const labelText = label && label.textContent ? label.textContent.trim() : '';
        if (labelText) {
            trigger.setAttribute('aria-label', labelText);
        }

        const valueText = document.createElement('span');
        valueText.className = 'select-value';

        const caret = document.createElement('span');
        caret.className = 'select-caret';
        caret.setAttribute('aria-hidden', 'true');

        trigger.appendChild(valueText);
        trigger.appendChild(caret);

        const menu = document.createElement('div');
        menu.className = 'select-menu';
        menu.setAttribute('role', 'listbox');

        const searchWrap = document.createElement('div');
        searchWrap.className = 'select-search';

        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'select-search-input';
        searchInput.setAttribute('autocomplete', 'off');
        searchInput.setAttribute('spellcheck', 'false');
        searchInput.placeholder = 'Buscar...';

        searchWrap.appendChild(searchInput);

        const optionsList = document.createElement('div');
        optionsList.className = 'select-options';

        menu.appendChild(searchWrap);
        menu.appendChild(optionsList);

        const syncValue = () => {
            const selectedOption = select.selectedOptions[0] || select.options[select.selectedIndex];
            valueText.textContent = selectedOption ? selectedOption.textContent : '';
            const disabled = !!select.disabled;
            trigger.disabled = disabled;
            wrapper.classList.toggle('is-disabled', disabled);
        };

        const normalize = (value) => String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        const applyFilter = () => {
            const query = normalize(searchInput.value).trim();
            optionsList.querySelectorAll('.select-option').forEach((btn) => {
                const button = btn;
                if (!query) {
                    button.classList.remove('is-hidden');
                    return;
                }

                const labelText = normalize(button.textContent);
                button.classList.toggle('is-hidden', !labelText.includes(query));
            });
        };

        const rebuildOptions = () => {
            optionsList.innerHTML = '';

            [...select.options].forEach((option) => {
                const optionButton = document.createElement('button');
                optionButton.type = 'button';
                optionButton.className = 'select-option';
                optionButton.textContent = option.textContent;
                optionButton.dataset.value = option.value;

                if (option.disabled) {
                    optionButton.disabled = true;
                }

                optionButton.setAttribute('role', 'option');
                optionButton.setAttribute('aria-selected', option.selected ? 'true' : 'false');

                bindTap(optionButton, () => {
                    if (option.disabled) {
                        return;
                    }

                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    closeAllSelectMenus();
                    trigger.focus();
                });

                optionsList.appendChild(optionButton);
            });

            applyFilter();
        };

        bindTap(trigger, () => {
            if (trigger.disabled) {
                return;
            }

            const willOpen = !wrapper.classList.contains('is-open');
            closeAllSelectMenus(willOpen ? wrapper : null);
            wrapper.classList.toggle('is-open', willOpen);

            if (willOpen) {
                searchInput.value = '';
                applyFilter();
                requestAnimationFrame(() => {
                    searchInput.focus();
                });
            }
        });

        select.addEventListener('change', () => {
            syncValue();
            menu.querySelectorAll('.select-option').forEach((btn) => {
                const button = btn;
                const isSelected = button.dataset.value === select.value;
                button.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            });
        });

        searchInput.addEventListener('input', applyFilter);

        searchInput.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }
            event.preventDefault();
            closeAllSelectMenus();
            trigger.focus();
        });

        wrapper.appendChild(trigger);
        wrapper.appendChild(menu);

        select.classList.add('is-enhanced-hidden');
        select.dataset.enhanced = 'true';
        select.insertAdjacentElement('afterend', wrapper);

        rebuildOptions();
        syncValue();

        const observer = new MutationObserver(() => {
            rebuildOptions();
            syncValue();
        });

        observer.observe(select, { childList: true, subtree: true, attributes: true });
    };

    document.querySelectorAll('select.js-enhanced-select').forEach((select) => buildSelectUi(select));

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (target && target.closest && target.closest('.select-ui')) {
            return;
        }
        closeAllSelectMenus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        closeAllSelectMenus();
    });

    const initPasswordToggles = () => {
        document.querySelectorAll('.js-password-toggle').forEach((toggle) => {
            const button = toggle instanceof HTMLButtonElement ? toggle : null;
            if (!button) {
                return;
            }

            const targetId = button.dataset.target || '';
            if (!targetId) {
                return;
            }

            const input = document.getElementById(targetId);
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const applyState = (isOn) => {
                input.type = isOn ? 'text' : 'password';
                button.classList.toggle('is-on', isOn);
                button.setAttribute('aria-pressed', isOn ? 'true' : 'false');
                button.setAttribute('aria-label', isOn ? 'Ocultar senha' : 'Mostrar senha');
            };

            applyState(input.type === 'text');

            bindTap(button, () => {
                applyState(input.type === 'password');
                try {
                    input.focus({ preventScroll: true });
                } catch (error) {
                    input.focus();
                }
            });
        });
    };

    const initRegisterPasswordValidation = () => {
        const passwordInput = document.getElementById('password');
        const confirmationInput = document.getElementById('password_confirmation');

        if (!(passwordInput instanceof HTMLInputElement) || !(confirmationInput instanceof HTMLInputElement)) {
            return;
        }

        const rulesList = document.querySelector('[data-password-rules]');
        const mismatchHelp = document.getElementById('password_mismatch_help');

        const evaluatePassword = (value) => {
            const text = String(value || '');
            return {
                length: text.length >= 8,
                upper: /[A-Z]/.test(text),
                lower: /[a-z]/.test(text),
                special: /[^A-Za-z0-9]/.test(text),
            };
        };

        const syncRules = () => {
            if (!(rulesList instanceof Element)) {
                return;
            }

            const results = evaluatePassword(passwordInput.value);
            rulesList.querySelectorAll('[data-rule]').forEach((ruleItem) => {
                const item = ruleItem instanceof HTMLElement ? ruleItem : null;
                if (!item) {
                    return;
                }

                const key = item.dataset.rule || '';
                item.classList.toggle('is-valid', !!results[key]);
            });
        };

        const syncConfirmation = () => {
            const confirmationValue = confirmationInput.value;
            const passwordValue = passwordInput.value;
            const hasSomething = confirmationValue.length > 0 || passwordValue.length > 0;
            const mismatch = hasSomething && confirmationValue.length > 0 && confirmationValue !== passwordValue;

            confirmationInput.classList.toggle('is-invalid', mismatch);
            confirmationInput.setAttribute('aria-invalid', mismatch ? 'true' : 'false');

            if (mismatchHelp instanceof HTMLElement) {
                mismatchHelp.hidden = !mismatch;
            }
        };

        const syncAll = () => {
            syncRules();
            syncConfirmation();
        };

        passwordInput.addEventListener('input', syncAll);
        confirmationInput.addEventListener('input', syncAll);

        syncAll();
    };

    initPasswordToggles();
    initRegisterPasswordValidation();
});
