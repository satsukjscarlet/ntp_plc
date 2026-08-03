(() => {
    'use strict';

    const config = window.monitoringLayoutEditorConfig;
    if (!config) return;

    const sections = Array.isArray(config.sections) ? config.sections : [];
    const tags = Array.isArray(config.tags) ? config.tags : [];

    const sectionSelect = document.getElementById('section-select');
    const rowSelect = document.getElementById('row-select');
    const columnSelect = document.getElementById('column-select');
    const showSwitch = document.getElementById('show-on-monitoring');
    const positionFields = document.getElementById('position-fields');
    const preview = document.getElementById('position-preview');
    const saveButton = document.getElementById('save-position');

    let selectedTag = null;

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
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const findSection = id =>
        sections.find(item => String(item.id) === String(id)) || null;

    const fillSelect = (select, items, selectedValue, emptyText) => {
        select.innerHTML = '';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = emptyText;
        select.appendChild(emptyOption);

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            option.selected = String(item.id) === String(selectedValue ?? '');
            select.appendChild(option);
        });

        select.disabled = items.length === 0;
    };

    const fillSections = () => {
        fillSelect(
            sectionSelect,
            sections,
            selectedTag?.section_id,
            '-- Chọn loại thông số --'
        );
    };

    const refreshRowsAndColumns = (keepCurrent = true) => {
        const section = findSection(sectionSelect.value);

        if (!section) {
            fillSelect(rowSelect, [], null, '-- Chọn dòng --');
            fillSelect(columnSelect, [], null, '-- Chọn cột --');
            updatePreview();
            return;
        }

        fillSelect(
            rowSelect,
            section.rows || [],
            keepCurrent ? selectedTag?.row_id : null,
            '-- Chọn dòng --'
        );

        fillSelect(
            columnSelect,
            section.columns || [],
            keepCurrent ? selectedTag?.column_id : null,
            '-- Chọn cột --'
        );

        updatePreview();
    };

    const updatePreview = () => {
        if (!showSwitch.checked) {
            preview.textContent = 'Không hiển thị trên màn hình giám sát';
            return;
        }

        const section = findSection(sectionSelect.value);
        const row = section?.rows?.find(
            item => String(item.id) === String(rowSelect.value)
        );
        const column = section?.columns?.find(
            item => String(item.id) === String(columnSelect.value)
        );

        const parts = [
            section?.name,
            row?.name,
            column?.name,
        ].filter(Boolean);

        preview.textContent = parts.length
            ? parts.join(' → ')
            : 'Chưa chọn đủ vị trí';
    };

    const renderSelectedTag = () => {
        document.getElementById('empty-selection').classList.add('d-none');
        document.getElementById('tag-editor').classList.remove('d-none');

        document.getElementById('selected-tag-name').textContent =
            selectedTag.name || 'Không có tên';

        document.getElementById('selected-tag-address').textContent =
            selectedTag.address || '';

        showSwitch.checked = Boolean(selectedTag.section_id);
        positionFields.classList.toggle('d-none', !showSwitch.checked);

        fillSections();
        refreshRowsAndColumns(true);
        updatePreview();
    };

    document.querySelectorAll('.js-select-tag').forEach(button => {
        button.addEventListener('click', () => {
            const id = Number(button.dataset.tagId);
            selectedTag = tags.find(tag => Number(tag.id) === id) || null;

            document.querySelectorAll('.js-select-tag').forEach(item =>
                item.classList.remove('active')
            );
            button.classList.add('active');

            if (selectedTag) renderSelectedTag();
        });
    });

    showSwitch?.addEventListener('change', () => {
        positionFields.classList.toggle('d-none', !showSwitch.checked);

        if (!showSwitch.checked) {
            sectionSelect.value = '';
            fillSelect(rowSelect, [], null, '-- Chọn dòng --');
            fillSelect(columnSelect, [], null, '-- Chọn cột --');
        }

        updatePreview();
    });

    sectionSelect?.addEventListener('change', () => {
        refreshRowsAndColumns(false);
    });

    rowSelect?.addEventListener('change', updatePreview);
    columnSelect?.addEventListener('change', updatePreview);

    saveButton?.addEventListener('click', async () => {
        if (!selectedTag) return;

        const url = config.updateUrlTemplate.replace(
            '__TAG_ID__',
            String(selectedTag.id)
        );

        saveButton.disabled = true;
        saveButton.textContent = 'Đang lưu...';

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({
                    monitoring_section_id: showSwitch.checked
                        ? (sectionSelect.value || null)
                        : null,
                    monitoring_row_id: showSwitch.checked
                        ? (rowSelect.value || null)
                        : null,
                    monitoring_column_id: showSwitch.checked
                        ? (columnSelect.value || null)
                        : null,
                }),
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không lưu được vị trí.');
            }

            selectedTag.section_id = showSwitch.checked
                ? (sectionSelect.value || null)
                : null;
            selectedTag.row_id = showSwitch.checked
                ? (rowSelect.value || null)
                : null;
            selectedTag.column_id = showSwitch.checked
                ? (columnSelect.value || null)
                : null;

            showMessage('success', result.message);
        } catch (error) {
            showMessage('danger', error.message);
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Lưu vị trí hiển thị';
        }
    });

    const submitQuickForm = async (form, url, itemType) => {
        const button = form.querySelector('button[type="submit"], button:not([type])');
        const formData = new FormData(form);

        button.disabled = true;
        button.textContent = 'Đang thêm...';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: formData,
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không thêm được dữ liệu.');
            }

            const sectionId = String(formData.get('section_id'));
            const section = findSection(sectionId);

            if (section) {
                if (itemType === 'row') {
                    section.rows.push(result.item);
                } else {
                    section.columns.push(result.item);
                }
            }

            form.reset();
            showMessage('success', result.message);

            if (
                selectedTag &&
                String(sectionSelect.value) === sectionId
            ) {
                refreshRowsAndColumns(false);
            }
        } catch (error) {
            showMessage('danger', error.message);
        } finally {
            button.disabled = false;
            button.textContent = itemType === 'row'
                ? 'Thêm dòng'
                : 'Thêm cột';
        }
    };

    document.getElementById('add-row-form')?.addEventListener(
        'submit',
        event => {
            event.preventDefault();
            submitQuickForm(event.currentTarget, config.addRowUrl, 'row');
        }
    );

    document.getElementById('add-column-form')?.addEventListener(
        'submit',
        event => {
            event.preventDefault();
            submitQuickForm(
                event.currentTarget,
                config.addColumnUrl,
                'column'
            );
        }
    );
})();
