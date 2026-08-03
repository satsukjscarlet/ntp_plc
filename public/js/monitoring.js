(() => {
    'use strict';

    const config = window.monitoringConfig;

    if (!config) {
        return;
    }

    const groupsContainer = document.getElementById('monitoring-groups');
    const errorContainer = document.getElementById('monitoring-error');
    const statusBadge = document.getElementById('machine-status');

    let loading = false;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const formatDateTime = (value) => {
        if (!value) {
            return '—';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).format(date);
    };

    const statusClass = (status) => {
        switch (status) {
            case 'ONLINE':
                return 'text-bg-success';
            case 'WARNING':
                return 'text-bg-warning';
            case 'OFFLINE':
                return 'text-bg-danger';
            default:
                return 'text-bg-secondary';
        }
    };

    const alarmClass = (state) => {
        switch (state) {
            case 'HIGH':
            case 'LOW':
                return 'text-bg-danger';
            case 'BAD':
                return 'text-bg-warning';
            case 'STALE':
                return 'text-bg-secondary';
            case 'NO_DATA':
                return 'text-bg-dark';
            default:
                return 'text-bg-success';
        }
    };

    const renderSummary = (data) => {
        statusBadge.className = `badge ${statusClass(data.machine.status)}`;
        statusBadge.textContent = data.machine.status_label;

        document.getElementById('summary-machine').textContent =
            `${data.machine.code} - ${data.machine.name ?? ''}`;

        document.getElementById('summary-total-tags').textContent =
            data.collector.total_tags;

        document.getElementById('summary-last-seen').textContent =
            formatDateTime(data.machine.last_seen_at);

        document.getElementById('summary-generated-at').textContent =
            formatDateTime(data.generated_at);
    };

    const renderTag = (tag) => `
        <tr data-tag-id="${tag.id}">
            <td>
                <div class="fw-semibold">
                    ${escapeHtml(tag.display_name)}
                </div>
                <div class="small text-muted">
                    ${escapeHtml(tag.address || tag.source_key || '')}
                </div>
            </td>

            <td class="text-end">
                <span class="fw-semibold">
                    ${escapeHtml(tag.formatted_value)}
                </span>
            </td>

            <td class="text-center">
                <span class="badge ${alarmClass(tag.alarm_state)}">
                    ${escapeHtml(tag.alarm_state)}
                </span>
            </td>

            <td class="text-end small text-muted">
                ${formatDateTime(tag.collected_at)}
            </td>
        </tr>
    `;

    const renderGroups = (groups) => {
        if (!groups.length) {
            groupsContainer.innerHTML = `
                <div class="alert alert-warning">
                    Chưa có tag nào được bật hiển thị.
                </div>
            `;
            return;
        }

        groupsContainer.innerHTML = groups.map(group => `
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <strong>${escapeHtml(group.name)}</strong>
                    <span class="badge text-bg-secondary">
                        ${group.total_tags} tag
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tên tag</th>
                                <th class="text-end">Giá trị</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${group.tags.map(renderTag).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `).join('');
    };

    const showError = (message) => {
        errorContainer.innerHTML = `
            <div class="alert alert-danger">
                ${escapeHtml(message)}
            </div>
        `;
    };

    const clearError = () => {
        errorContainer.innerHTML = '';
    };

    const loadMonitoringData = async () => {
        if (loading) {
            return;
        }

        loading = true;

        try {
            const response = await fetch(config.apiUrl, {
                headers: {
                    Accept: 'application/json',
                },
                cache: 'no-store',
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Không tải được dữ liệu giám sát.'
                );
            }

            clearError();
            renderSummary(result.data);
            renderGroups(result.data.groups);
        } catch (error) {
            showError(error.message);
            statusBadge.className = 'badge text-bg-danger';
            statusBadge.textContent = 'Lỗi API';
        } finally {
            loading = false;
        }
    };

    loadMonitoringData();
    window.setInterval(loadMonitoringData, config.refreshInterval);
})();
