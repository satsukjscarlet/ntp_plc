(() => {
    'use strict';

    const config = window.monitoringLayoutDragDropConfig;
    if (!config) return;

    const tags = new Map(
        (Array.isArray(config.tags) ? config.tags : [])
            .map(tag => [Number(tag.id), tag])
    );

    let draggedTagId = null;
    let settingsModal = null;

    const escapeHtml = value => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const nullableNumber = value => {
        const normalized = String(value ?? '').trim();
        return normalized === '' ? null : Number(normalized);
    };

    const showMessage = (type, message) => {
        const holder = document.getElementById('page-message');
        holder.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${escapeHtml(message)}
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Đóng"></button>
            </div>
        `;
    };

    const showSettingsErrors = errors => {
        const holder = document.getElementById('settings-errors');
        const messages = Object.values(errors || {}).flat();

        if (!messages.length) {
            holder.innerHTML = '';
            return;
        }

        holder.innerHTML = `
            <div class="alert alert-danger">
                ${messages.map(message => `<div>${escapeHtml(message)}</div>`).join('')}
            </div>
        `;
    };

    const createTagCard = tag => {
        const card = document.createElement('div');
        card.className = 'plc-tag-card';
        card.draggable = true;
        card.dataset.tagId = tag.id;
        card.dataset.searchText =
            `${tag.name || ''} ${tag.source_name || ''} ${tag.address || ''}`.toLowerCase();

        const removeButton = tag.section_id
            ? `<button type="button"
                       class="tag-card-action tag-remove-position"
                       title="Bỏ vị trí"
                       aria-label="Bỏ vị trí">×</button>`
            : '';

        card.innerHTML = `
            <span class="plc-tag-card-name">
                ${escapeHtml(tag.name || 'Không có tên')}
            </span>
            <span class="plc-tag-card-address">
                ${escapeHtml(tag.address || '')}
            </span>
            <span class="plc-tag-card-settings-summary">
                ${escapeHtml(tag.display_unit || '')}
                ${tag.alarm_enabled ? ' • Cảnh báo' : ''}
            </span>
            <button type="button"
                    class="tag-card-action tag-open-settings"
                    title="Cấu hình hiển thị"
                    aria-label="Cấu hình hiển thị">⚙</button>
            ${removeButton}
        `;

        card.addEventListener('dragstart', event => {
            if (event.target.closest('button')) {
                event.preventDefault();
                return;
            }

            draggedTagId = Number(tag.id);
            card.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(tag.id));
        });

        card.addEventListener('dragend', () => {
            draggedTagId = null;
            card.classList.remove('dragging');
            clearDragState();
        });

        card.querySelector('.tag-open-settings')?.addEventListener(
            'click',
            event => {
                event.stopPropagation();
                openSettings(tag.id);
            }
        );

        card.querySelector('.tag-remove-position')?.addEventListener(
            'click',
            async event => {
                event.stopPropagation();

                try {
                    await moveTag(tag.id, null, null, null, false);
                } catch (error) {
                    showMessage('danger', error.message);
                }
            }
        );

        return card;
    };

    const getCell = tag => {
        if (!tag.section_id || !tag.row_id || !tag.column_id) return null;

        return document.querySelector(
            `.layout-cell[data-section-id="${tag.section_id}"]` +
            `[data-row-id="${tag.row_id}"]` +
            `[data-column-id="${tag.column_id}"]`
        );
    };

    const renderAll = () => {
        document.querySelectorAll('.cell-tag-container').forEach(container => {
            container.innerHTML = '';
        });

        document.querySelectorAll('.layout-cell').forEach(cell => {
            cell.classList.remove('occupied');
        });

        const unassignedList = document.getElementById('unassigned-tag-list');
        unassignedList.innerHTML = '';

        [...tags.values()]
            .sort((a, b) => (a.name || '').localeCompare(
                b.name || '',
                'vi',
                { sensitivity: 'base' }
            ))
            .forEach(tag => {
                const card = createTagCard(tag);
                const cell = getCell(tag);

                if (cell) {
                    cell.querySelector('.cell-tag-container').appendChild(card);
                    cell.classList.add('occupied');
                } else {
                    unassignedList.appendChild(card);
                }
            });

        applySearchFilter();
    };

    const clearDragState = () => {
        document.querySelectorAll('.drag-over').forEach(element => {
            element.classList.remove('drag-over');
        });
    };

    const requestJson = async (url, method, body) => {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            body: JSON.stringify(body),
        });

        const result = await response.json().catch(() => ({
            success: false,
            message: `Máy chủ trả về HTTP ${response.status}.`,
        }));

        return { response, result };
    };

    const moveTag = async (
        tagId,
        sectionId,
        rowId,
        columnId,
        replaceExisting
    ) => {
        const url = config.updateUrlTemplate.replace(
            '__TAG_ID__',
            String(tagId)
        );

        const { response, result } = await requestJson(url, 'PUT', {
            monitoring_section_id: sectionId,
            monitoring_row_id: rowId,
            monitoring_column_id: columnId,
            replace_existing: replaceExisting,
        });

        if (response.status === 409 && result.requires_confirmation) {
            if (window.confirm(result.message)) {
                return moveTag(tagId, sectionId, rowId, columnId, true);
            }

            return;
        }

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Không lưu được vị trí.');
        }

        if (result.tag) {
            tags.set(Number(result.tag.id), result.tag);
        }

        if (result.replaced_tag) {
            tags.set(Number(result.replaced_tag.id), result.replaced_tag);
        }

        renderAll();
        showMessage('success', result.message);
    };

    const updatePreview = () => {
        const name = document.getElementById('settings-display-name').value.trim();
        const unit = document.getElementById('settings-display-unit').value.trim();
        const decimals = Math.min(
            Math.max(
                Number(document.getElementById('settings-display-decimals').value || 0),
                0
            ),
            6
        );
        const color = document.getElementById('settings-display-color').value.trim();

        document.getElementById('settings-preview-name').textContent =
            name || 'Tên tag';

        document.getElementById('settings-preview-value').textContent =
            Number(215.345678).toFixed(decimals);

        document.getElementById('settings-preview-unit').textContent = unit;

        const preview = document.getElementById('settings-preview-card');
        preview.style.borderColor = color || '#9eb3c7';
    };

    const openSettings = tagId => {
        const tag = tags.get(Number(tagId));
        if (!tag) return;

        document.getElementById('settings-errors').innerHTML = '';
        document.getElementById('settings-tag-id').value = tag.id;
        document.getElementById('settings-tag-source').textContent =
            `${tag.source_name || tag.name || ''} • ${tag.address || ''}`;
        document.getElementById('settings-display-name').value =
            tag.name || '';
        document.getElementById('settings-display-unit').value =
            tag.display_unit || '';
        document.getElementById('settings-display-decimals').value =
            tag.display_decimals ?? 1;
        document.getElementById('settings-warning-low').value =
            tag.warning_low ?? '';
        document.getElementById('settings-warning-high').value =
            tag.warning_high ?? '';
        document.getElementById('settings-alarm-enabled').checked =
            Boolean(tag.alarm_enabled);
        document.getElementById('settings-blink-on-alarm').checked =
            Boolean(tag.blink_on_alarm);
        document.getElementById('settings-chart-enabled').checked =
            Boolean(tag.chart_enabled);
        document.getElementById('settings-display-color').value =
            tag.display_color || '';

        const picker = document.getElementById('settings-color-picker');
        picker.value = /^#[0-9a-f]{6}$/i.test(tag.display_color || '')
            ? tag.display_color
            : '#1f4e78';

        updatePreview();

        settingsModal ??= new bootstrap.Modal(
            document.getElementById('tag-settings-modal')
        );
        settingsModal.show();
    };

    const saveSettings = async event => {
        event.preventDefault();

        const tagId = Number(
            document.getElementById('settings-tag-id').value
        );

        const url = config.updateUrlTemplate.replace(
            '__TAG_ID__',
            String(tagId)
        );

        const button = document.getElementById('settings-save-button');
        button.disabled = true;
        button.textContent = 'Đang lưu...';
        showSettingsErrors({});

        try {
            const { response, result } = await requestJson(url, 'PUT', {
                display_name:
                    document.getElementById('settings-display-name').value.trim() || null,
                display_unit:
                    document.getElementById('settings-display-unit').value.trim() || null,
                display_decimals:
                    Number(document.getElementById('settings-display-decimals').value || 0),
                warning_low:
                    nullableNumber(document.getElementById('settings-warning-low').value),
                warning_high:
                    nullableNumber(document.getElementById('settings-warning-high').value),
                alarm_enabled:
                    document.getElementById('settings-alarm-enabled').checked,
                blink_on_alarm:
                    document.getElementById('settings-blink-on-alarm').checked,
                chart_enabled:
                    document.getElementById('settings-chart-enabled').checked,
                display_color:
                    document.getElementById('settings-display-color').value.trim() || null,
            });

            if (!response.ok || !result.success) {
                showSettingsErrors(result.errors || {
                    general: [result.message || 'Không lưu được cấu hình.'],
                });
                return;
            }

            tags.set(Number(result.tag.id), result.tag);
            renderAll();
            settingsModal?.hide();
            showMessage('success', result.message);
        } finally {
            button.disabled = false;
            button.textContent = 'Lưu cấu hình';
        }
    };

    document.querySelectorAll('.layout-cell').forEach(cell => {
        cell.addEventListener('dragover', event => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            cell.classList.add('drag-over');
        });

        cell.addEventListener('dragleave', () => {
            cell.classList.remove('drag-over');
        });

        cell.addEventListener('drop', async event => {
            event.preventDefault();
            cell.classList.remove('drag-over');

            const tagId = Number(
                event.dataTransfer.getData('text/plain') || draggedTagId
            );

            if (!tagId) return;

            try {
                await moveTag(
                    tagId,
                    Number(cell.dataset.sectionId),
                    Number(cell.dataset.rowId),
                    Number(cell.dataset.columnId),
                    false
                );
            } catch (error) {
                showMessage('danger', error.message);
            }
        });
    });

    const unassignedZone = document.getElementById('unassigned-dropzone');

    unassignedZone.addEventListener('dragover', event => {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        unassignedZone.classList.add('drag-over');
    });

    unassignedZone.addEventListener('dragleave', event => {
        if (!unassignedZone.contains(event.relatedTarget)) {
            unassignedZone.classList.remove('drag-over');
        }
    });

    unassignedZone.addEventListener('drop', async event => {
        event.preventDefault();
        unassignedZone.classList.remove('drag-over');

        const tagId = Number(
            event.dataTransfer.getData('text/plain') || draggedTagId
        );

        if (!tagId) return;

        try {
            await moveTag(tagId, null, null, null, false);
        } catch (error) {
            showMessage('danger', error.message);
        }
    });

    const searchInput = document.getElementById('tag-search');

    const applySearchFilter = () => {
        const keyword = (searchInput.value || '').trim().toLowerCase();

        document
            .querySelectorAll('#unassigned-tag-list .plc-tag-card')
            .forEach(card => {
                card.hidden = Boolean(keyword) &&
                    !card.dataset.searchText.includes(keyword);
            });
    };

    searchInput.addEventListener('input', applySearchFilter);
    document.getElementById('tag-settings-form')
        .addEventListener('submit', saveSettings);

    [
        'settings-display-name',
        'settings-display-unit',
        'settings-display-decimals',
        'settings-display-color',
    ].forEach(id => {
        document.getElementById(id).addEventListener('input', updatePreview);
    });

    document.getElementById('settings-color-picker')
        .addEventListener('input', event => {
            document.getElementById('settings-display-color').value =
                event.target.value;
            updatePreview();
        });

    renderAll();
})();
