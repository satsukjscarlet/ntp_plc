(() => {
    'use strict';

    const config = window.monitoringLayoutConfig;
    if (!config) return;

    const sectionsById = new Map(
        config.sections.map(section => [String(section.id), section])
    );

    const escapeHtml = value => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const showMessage = (type, message) => {
        document.getElementById('page-message').innerHTML = `
            <div class="alert alert-${type}">
                ${escapeHtml(message)}
            </div>
        `;
    };

    const fillOptions = (select, items, currentValue, emptyText) => {
        select.innerHTML = `<option value="">${escapeHtml(emptyText)}</option>`;

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;

            if (String(item.id) === String(currentValue || '')) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    };

    const updateDependentSelects = rowElement => {
        const sectionSelect = rowElement.querySelector('.js-section');
        const rowSelect = rowElement.querySelector('.js-row');
        const columnSelect = rowElement.querySelector('.js-column');

        const section = sectionsById.get(sectionSelect.value);

        if (!section) {
            fillOptions(rowSelect, [], null, 'Chưa chọn dòng');
            fillOptions(columnSelect, [], null, 'Chưa chọn cột');
            rowSelect.disabled = true;
            columnSelect.disabled = true;
            return;
        }

        fillOptions(
            rowSelect,
            section.rows || [],
            rowSelect.dataset.current,
            'Chưa chọn dòng'
        );

        fillOptions(
            columnSelect,
            section.columns || [],
            columnSelect.dataset.current,
            'Chưa chọn cột'
        );

        rowSelect.disabled = !(section.rows || []).length;
        columnSelect.disabled = !(section.columns || []).length;

        rowSelect.dataset.current = '';
        columnSelect.dataset.current = '';
    };

    document.querySelectorAll('[data-tag-row]').forEach(rowElement => {
        const sectionSelect = rowElement.querySelector('.js-section');

        updateDependentSelects(rowElement);

        sectionSelect.addEventListener('change', () => {
            rowElement.querySelector('.js-row').dataset.current = '';
            rowElement.querySelector('.js-column').dataset.current = '';
            updateDependentSelects(rowElement);
        });
    });

    document.querySelectorAll('.js-save-position').forEach(button => {
        button.addEventListener('click', async () => {
            const rowElement = button.closest('[data-tag-row]');
            const sectionId = rowElement.querySelector('.js-section').value;
            const rowId = rowElement.querySelector('.js-row').value;
            const columnId = rowElement.querySelector('.js-column').value;

            button.disabled = true;
            button.textContent = 'Đang lưu...';

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'PUT',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify({
                        monitoring_section_id: sectionId || null,
                        monitoring_row_id: rowId || null,
                        monitoring_column_id: columnId || null,
                    }),
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Không lưu được vị trí.');
                }

                showMessage('success', result.message);
            } catch (error) {
                showMessage('danger', error.message);
            } finally {
                button.disabled = false;
                button.textContent = 'Lưu vị trí';
            }
        });
    });
})();
