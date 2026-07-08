document.addEventListener('DOMContentLoaded', () => {
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

        const label = select.closest('.field')?.querySelector(`label[for="${select.id}"]`);
        if (label) {
            trigger.setAttribute('aria-label', label.textContent?.trim() || 'Selecionar');
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

                optionButton.addEventListener('click', () => {
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

        trigger.addEventListener('click', () => {
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
        if (target && target.closest('.select-ui')) {
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
});
