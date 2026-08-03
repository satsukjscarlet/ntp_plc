(() => {
    'use strict';

    const config = window.machineTagConfig;
    const modalElement = document.getElementById('tag-edit-modal');

    if (!config || !modalElement) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = document.getElementById('tag-edit-form');
    const saveButton = document.getElementById('btn-save-tag');
    const alertBox = document.getElementById('tag-alert');
    const editErrors = document.getElementById('edit-errors');

    const urlForTag = (tagId) =>
        config.editUrlTemplate.replace('__TAG__', String(tagId));

    const showAlert = (message, type = 'success') => {
        alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show">
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    };

    const showErrors = (errors) => {
        const messages = Object.values(errors || {}).flat();

        editErrors.innerHTML = messages.length
            ? `<div class="alert alert-danger"><ul class="mb-0">${
                messages.map(message => `<li>${escapeHtml(message)}</li>`).join('')
            }</ul></div>`
            : '';
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const setLoading = (button, loading, text = 'Đang xử lý...') => {
        if (loading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = text;
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || 'Lưu cấu hình';
        }
    };

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.btn-edit-tag');

        if (!button) {
            return;
        }

        editErrors.innerHTML = '';
        setLoading(button, true, 'Đang tải...');

        try {
            const response = await fetch(urlForTag(button.dataset.tagId), {
                headers: {
                    Accept: 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Không tải được dữ liệu tag.');
            }

            const tag = result.tag;

            document.getElementById('edit-tag-id').value = tag.id;
            document.getElementById('edit-source-name').textContent = tag.source_name;
            document.getElementById('edit-address').textContent =
                tag.address || 'Không có địa chỉ';
            document.getElementById('edit-data-type').textContent =
                tag.data_type || 'Không xác định';

            document.getElementById('edit-display-name').value =
                tag.display_name || tag.source_name || '';
            document.getElementById('edit-unit').value = tag.unit || '';
            document.getElementById('edit-display-group').value =
                tag.display_group || '';
            document.getElementById('edit-display-order').value =
                tag.display_order ?? 0;
            document.getElementById('edit-decimal-places').value =
                tag.decimal_places ?? 1;
            document.getElementById('edit-history-deadband').value =
                tag.history_deadband ?? 0;
            document.getElementById('edit-alarm-low').value =
                tag.alarm_low ?? '';
            document.getElementById('edit-alarm-high').value =
                tag.alarm_high ?? '';
            document.getElementById('edit-is-visible').checked =
                Boolean(tag.is_visible);
            document.getElementById('edit-history-enabled').checked =
                Boolean(tag.history_enabled);

            modal.show();
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            setLoading(button, false, 'Sửa');
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const tagId = document.getElementById('edit-tag-id').value;
        const formData = new FormData(form);

        formData.set(
            'is_visible',
            document.getElementById('edit-is-visible').checked ? '1' : '0'
        );
        formData.set(
            'history_enabled',
            document.getElementById('edit-history-enabled').checked ? '1' : '0'
        );

        editErrors.innerHTML = '';
        setLoading(saveButton, true);

        try {
            const response = await fetch(urlForTag(tagId), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: formData,
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422) {
                    showErrors(result.errors);
                    return;
                }

                throw new Error(result.message || 'Không cập nhật được tag.');
            }

            updateRow(result.tag);
            modal.hide();
            showAlert(result.message);
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            setLoading(saveButton, false);
        }
    });

    document.getElementById('check-all')?.addEventListener('change', (event) => {
        document.querySelectorAll('.tag-checkbox').forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });
    });

    document.getElementById('btn-bulk-update')?.addEventListener(
        'click',
        async (event) => {
            const button = event.currentTarget;
            const tagIds = [...document.querySelectorAll('.tag-checkbox:checked')]
                .map(checkbox => Number(checkbox.value));

            if (tagIds.length === 0) {
                showAlert('Bạn chưa chọn tag.', 'warning');
                return;
            }

            const payload = {
                tag_ids: tagIds,
            };

            const displayGroup =
                document.getElementById('bulk-display-group').value.trim();
            const isVisible =
                document.getElementById('bulk-is-visible').value;
            const historyEnabled =
                document.getElementById('bulk-history-enabled').value;
            const deadband =
                document.getElementById('bulk-history-deadband').value;

            if (displayGroup !== '') {
                payload.display_group = displayGroup;
            }

            if (isVisible !== '') {
                payload.is_visible = isVisible === '1';
            }

            if (historyEnabled !== '') {
                payload.history_enabled = historyEnabled === '1';
            }

            if (deadband !== '') {
                payload.history_deadband = Number(deadband);
            }

            if (Object.keys(payload).length === 1) {
                showAlert('Chưa chọn nội dung cần cập nhật.', 'warning');
                return;
            }

            setLoading(button, true);

            try {
                const response = await fetch(config.bulkUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Cập nhật hàng loạt thất bại.');
                }

                showAlert(result.message);
                window.setTimeout(() => window.location.reload(), 700);
            } catch (error) {
                showAlert(error.message, 'danger');
            } finally {
                setLoading(button, false);
            }
        }
    );

    const updateRow = (tag) => {
        const row = document.getElementById(`tag-row-${tag.id}`);

        if (!row) {
            return;
        }

        row.querySelector('.tag-display-name').textContent =
            tag.display_name || '';
        row.querySelector('.tag-unit').textContent =
            tag.unit || 'Chưa có đơn vị';
        row.querySelector('.tag-display-group').textContent =
            tag.display_group || 'Chưa phân nhóm';
        row.querySelector('.tag-history-deadband').textContent =
            tag.history_deadband;

        row.querySelector('.tag-is-visible').innerHTML = tag.is_visible
            ? '<span class="badge text-bg-success">Hiện</span>'
            : '<span class="badge text-bg-secondary">Ẩn</span>';

        row.querySelector('.tag-history-enabled').innerHTML =
            tag.history_enabled
                ? '<span class="badge text-bg-primary">Bật</span>'
                : '<span class="badge text-bg-secondary">Tắt</span>';
    };
})();
