export function initSearchableSelects() {
    document.querySelectorAll('[data-searchable-select]').forEach((root) => {
        if (root.dataset.searchableReady === '1') {
            return;
        }

        root.dataset.searchableReady = '1';

        const hidden = root.querySelector('[data-searchable-value]');
        const input = root.querySelector('[data-searchable-input]');
        const list = root.querySelector('[data-searchable-list]');

        if (!hidden || !input || !list) {
            return;
        }

        document.body.appendChild(list);

        const options = Array.from(list.querySelectorAll('[data-value]')).map((item) => ({
            value: item.dataset.value ?? '',
            label: item.dataset.label ?? item.textContent?.trim() ?? '',
            element: item,
        }));

        let activeIndex = -1;

        const positionList = () => {
            const rect = input.getBoundingClientRect();

            list.style.position = 'fixed';
            list.style.top = `${rect.bottom + 4}px`;
            list.style.left = `${rect.left}px`;
            list.style.width = `${rect.width}px`;
            list.style.zIndex = '60';
        };

        const closeList = () => {
            list.hidden = true;
            list.style.display = 'none';
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
            options.forEach(({ element }) => element.classList.remove('is-active'));
        };

        const openList = () => {
            positionList();
            list.hidden = false;
            list.style.display = 'block';
            input.setAttribute('aria-expanded', 'true');
        };

        const filteredOptions = () => {
            const query = input.value.trim().toLowerCase();

            return options.filter(({ label }) => label.toLowerCase().includes(query));
        };

        const renderList = () => {
            const query = input.value.trim().toLowerCase();
            let visibleCount = 0;

            options.forEach(({ label, element }) => {
                const visible = label.toLowerCase().includes(query);
                element.hidden = !visible;
                element.classList.remove('is-active');

                if (visible) {
                    visibleCount += 1;
                }
            });

            let empty = list.querySelector('[data-searchable-empty]');

            if (visibleCount === 0) {
                if (!empty) {
                    empty = document.createElement('li');
                    empty.dataset.searchableEmpty = '1';
                    empty.className = 'ziifra-searchable-select-empty';
                    empty.textContent = list.dataset.emptyText || 'No matches';
                    list.appendChild(empty);
                }

                empty.hidden = false;
            } else if (empty) {
                empty.hidden = true;
            }

            activeIndex = -1;
        };

        const selectOption = (option) => {
            hidden.value = option.value;
            input.value = option.label;
            options.forEach(({ element, value }) => {
                element.classList.toggle('is-selected', value === option.value);
            });
            closeList();
        };

        const setActive = (index) => {
            const visible = filteredOptions();

            if (visible.length === 0) {
                return;
            }

            activeIndex = ((index % visible.length) + visible.length) % visible.length;
            options.forEach(({ element }) => element.classList.remove('is-active'));
            visible[activeIndex].element.classList.add('is-active');
            visible[activeIndex].element.scrollIntoView({ block: 'nearest' });
        };

        input.addEventListener('focus', () => {
            renderList();
            openList();
        });

        input.addEventListener('input', () => {
            hidden.value = '';
            options.forEach(({ element }) => element.classList.remove('is-selected'));
            renderList();
            openList();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                renderList();
                openList();
                setActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                renderList();
                openList();
                setActive(activeIndex <= 0 ? filteredOptions().length - 1 : activeIndex - 1);
            } else if (event.key === 'Enter') {
                if (list.hidden) {
                    return;
                }

                event.preventDefault();
                const visible = filteredOptions();

                if (activeIndex >= 0 && visible[activeIndex]) {
                    selectOption(visible[activeIndex]);
                } else if (visible.length === 1) {
                    selectOption(visible[0]);
                }
            } else if (event.key === 'Escape') {
                closeList();
                input.blur();
            }
        });

        list.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        list.addEventListener('click', (event) => {
            const item = event.target.closest('[data-value]');

            if (!item || item.hidden) {
                return;
            }

            selectOption({
                value: item.dataset.value ?? '',
                label: item.dataset.label ?? item.textContent?.trim() ?? '',
                element: item,
            });
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target) && !list.contains(event.target)) {
                closeList();

                if (!hidden.value) {
                    input.value = '';
                } else {
                    const selected = options.find(({ value }) => value === hidden.value);
                    input.value = selected?.label ?? input.value;
                }
            }
        });

        window.addEventListener('resize', () => {
            if (!list.hidden) {
                positionList();
            }
        });

        window.addEventListener('scroll', () => {
            if (!list.hidden) {
                positionList();
            }
        }, true);
    });
}

initSearchableSelects();
