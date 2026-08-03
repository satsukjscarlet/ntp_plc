(() => {
    'use strict';

    const config = window.monitoringRuntimeConfig;
    if (!config || !config.layout) return;

    const sectionsHolder = document.getElementById('runtime-sections');
    const connectionText = document.getElementById('runtime-connection-text');
    const statusDot = document.getElementById('runtime-status-dot');
    const lastUpdate = document.getElementById('runtime-last-update');
    const summaryElements = {
        total: document.getElementById('runtime-summary-total'),
        normal: document.getElementById('runtime-summary-normal'),
        alarm: document.getElementById('runtime-summary-alarm'),
        high: document.getElementById('runtime-summary-high'),
        low: document.getElementById('runtime-summary-low'),
        stale: document.getElementById('runtime-summary-stale'),
    };

    const escapeHtml = value => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const normalizeText = value => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    const cellId = tagId => `runtime-tag-${tagId}`;

    const formatTime = value => {
        if (!value) return 'Chưa có dữ liệu';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;

        return date.toLocaleTimeString('vi-VN');
    };

    const renderTagCard = (tag, options = {}) => {
        if (!tag) {
            return '<div class="runtime-empty-cell" aria-label="Chưa bố trí tag"></div>';
        }

        const zoneLabel = options.zoneLabel
            ? `<div class="runtime-zone-label">${escapeHtml(options.zoneLabel)}</div>`
            : '';

        const rowLabel = options.rowLabel
            ? `<div class="runtime-card-row-label">${escapeHtml(options.rowLabel)}</div>`
            : '';

        return `
            <div class="runtime-tag-card"
                 id="${cellId(tag.id)}"
                 data-tag-id="${tag.id}"
                 data-chart-enabled="${tag.chart_enabled ? '1' : '0'}"
                 tabindex="${tag.chart_enabled ? '0' : '-1'}"
                 role="${tag.chart_enabled ? 'button' : 'group'}"
                 aria-label="${tag.chart_enabled ? `Mở biểu đồ ${escapeHtml(tag.name || '')}` : escapeHtml(tag.name || '')}">
                ${zoneLabel}
                ${rowLabel}

                <div class="runtime-tag-name"
                     title="${escapeHtml(tag.name || '')}">
                    ${escapeHtml(tag.name || '')}
                </div>

                <div class="runtime-tag-value-line">
                    <span class="runtime-tag-value">
                        ${escapeHtml(tag.formatted_value ?? '--')}
                    </span>
                    <span class="runtime-tag-unit">
                        ${escapeHtml(tag.unit || '')}
                    </span>
                </div>

                <div class="runtime-tag-status">
                    ${escapeHtml(tag.status?.label || 'Bình thường')}
                </div>

                <div class="runtime-tag-time">
                    ${escapeHtml(formatTime(tag.collected_at))}
                </div>

                ${tag.chart_enabled ? `
                    <div class="runtime-tag-chart-hint">
                        Bấm để xem Trend
                    </div>
                ` : ''}
            </div>
        `;
    };

    const isTemperatureSection = section => {
        const sectionName = normalizeText(section.name);

        if (
            sectionName.includes('nhiet')
            || sectionName.includes('temperature')
            || sectionName.includes('heater')
        ) {
            return true;
        }

        const populatedCells = (section.rows || []).flatMap(
            row => (row.cells || []).filter(cell => cell.tag)
        );

        if (!populatedCells.length) return false;

        const temperatureTags = populatedCells.filter(cell => {
            const tagText = normalizeText(
                `${cell.tag?.name || ''} ${cell.tag?.unit || ''}`
            );

            return tagText.includes('nhiet')
                || tagText.includes('temperature')
                || tagText.includes('°c');
        });

        return temperatureTags.length / populatedCells.length >= 0.5;
    };

    const shouldUseCardGrid = section => {
        return isTemperatureSection(section)
            || (section.columns || []).length > 8;
    };

    const renderGridSection = section => {
        const groups = (section.rows || []).map(row => {
            const cards = (row.cells || [])
                .map((cell, index) => {
                    if (!cell.tag) return '';

                    const column = (section.columns || [])[index];

                    return renderTagCard(cell.tag, {
                        zoneLabel: column?.name || '',
                        rowLabel: row.name || '',
                    });
                })
                .filter(Boolean)
                .join('');

            if (!cards) return '';

            return `
                <div class="runtime-grid-group">
                    <div class="runtime-grid-group-title">
                        ${escapeHtml(row.name || 'Thông số')}
                    </div>
                    <div class="runtime-card-grid">
                        ${cards}
                    </div>
                </div>
            `;
        }).filter(Boolean).join('');

        return `
            <section class="runtime-section runtime-section-grid">
                <div class="runtime-section-title">
                    <span>${escapeHtml(section.name)}</span>
                    <span class="runtime-section-hint">
                        Dạng thẻ tự động xuống dòng
                    </span>
                </div>
                <div class="runtime-grid-wrapper">
                    ${groups || `
                        <div class="runtime-grid-empty">
                            Chưa bố trí tag cho khu vực này.
                        </div>
                    `}
                </div>
            </section>
        `;
    };

    const renderTableSection = section => `
        <section class="runtime-section">
            <div class="runtime-section-title">
                ${escapeHtml(section.name)}
            </div>

            <div class="runtime-table-wrapper">
                <table class="runtime-table">
                    <thead>
                        <tr>
                            <th class="runtime-row-name">Dòng / Cột</th>
                            ${(section.columns || []).map(column => `
                                <th title="${escapeHtml(column.name)}">
                                    ${escapeHtml(column.name)}
                                </th>
                            `).join('')}
                        </tr>
                    </thead>

                    <tbody>
                        ${(section.rows || []).map(row => `
                            <tr>
                                <th class="runtime-row-name"
                                    title="${escapeHtml(row.name)}">
                                    ${escapeHtml(row.name)}
                                </th>

                                ${(row.cells || []).map(cell => `
                                    <td class="runtime-cell">
                                        ${renderTagCard(cell.tag)}
                                    </td>
                                `).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </section>
    `;

    const renderLayout = () => {
        const html = (config.layout.sections || [])
            .map(section => shouldUseCardGrid(section)
                ? renderGridSection(section)
                : renderTableSection(section)
            )
            .join('');

        sectionsHolder.innerHTML = html || `
            <div class="alert alert-warning">
                Chưa có cấu hình layout cho máy này.
            </div>
        `;

        Object.values(getInitialTags()).forEach(updateTagCard);
    };

    const getInitialTags = () => {
        const result = {};

        (config.layout.sections || []).forEach(section => {
            (section.rows || []).forEach(row => {
                (row.cells || []).forEach(cell => {
                    if (cell.tag) {
                        result[cell.tag.id] = cell.tag;
                    }
                });
            });
        });

        return result;
    };

    const updateTagCard = tag => {
        const card = document.getElementById(cellId(tag.id));
        if (!card) return;

        card.querySelector('.runtime-tag-value').textContent =
            tag.formatted_value ?? '--';

        card.querySelector('.runtime-tag-unit').textContent =
            tag.unit || '';

        card.querySelector('.runtime-tag-status').textContent =
            tag.status?.label || 'Bình thường';

        card.querySelector('.runtime-tag-time').textContent =
            formatTime(tag.collected_at);

        card.classList.remove(
            'status-normal',
            'status-high',
            'status-low',
            'stale',
            'blink-alarm'
        );

        card.classList.add(`status-${tag.status?.code || 'normal'}`);

        if (tag.is_stale) {
            card.classList.add('stale');
        }

        if (
            tag.blink_on_alarm &&
            ['high', 'low'].includes(tag.status?.code)
        ) {
            card.classList.add('blink-alarm');
        }

        /*
         * Không để màu cấu hình ghi đè màu cảnh báo.
         * Màu riêng chỉ áp dụng khi trạng thái bình thường.
         */
        if (
            tag.display_color
            && !['high', 'low'].includes(tag.status?.code)
            && !tag.is_stale
        ) {
            card.style.borderColor = tag.display_color;
        } else {
            card.style.removeProperty('border-color');
        }
    };

    const setConnectionState = (connection, time = null) => {
        const state = typeof connection === 'string'
            ? {code: connection, label: connection}
            : (connection || {
                code: 'offline',
                label: 'Mất kết nối cập nhật',
            });

        statusDot.classList.remove('online', 'delayed', 'offline');
        statusDot.classList.add(state.code || 'offline');

        connectionText.textContent =
            state.label || 'Không xác định trạng thái';

        if (time) {
            lastUpdate.textContent = formatTime(time);
        }
    };

    const updateSummary = summary => {
        Object.entries(summaryElements).forEach(([key, element]) => {
            if (!element) return;
            element.textContent = String(summary?.[key] ?? 0);
        });

        document.body.classList.toggle(
            'runtime-has-alarm',
            Number(summary?.alarm || 0) > 0
        );
    };

    const refreshData = async () => {
        try {
            const response = await fetch(config.dataUrl, {
                headers: {
                    Accept: 'application/json',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            Object.values(result.tags || {}).forEach(updateTagCard);
            updateSummary(result.summary || {});
            setConnectionState(
                result.connection || {
                    code: 'online',
                    label: 'Đang cập nhật dữ liệu',
                },
                result.latest_collected_at || result.server_time
            );
        } catch (error) {
            setConnectionState({
                code: 'offline',
                label: 'Không gọi được API giám sát',
            });
            console.error('Monitoring runtime refresh failed:', error);
        }
    };


    const trendState = {
        tagId: null,
        range: '1h',
        data: null,
        modal: null,
    };

    const trendElements = {
        modal: document.getElementById('runtime-trend-modal'),
        title: document.getElementById('runtime-trend-title'),
        subtitle: document.getElementById('runtime-trend-subtitle'),
        message: document.getElementById('runtime-trend-message'),
        loading: document.getElementById('runtime-trend-loading'),
        chart: document.getElementById('runtime-trend-chart'),
        tooltip: document.getElementById('runtime-trend-tooltip'),
        current: document.getElementById('runtime-trend-current'),
        minimum: document.getElementById('runtime-trend-min'),
        maximum: document.getElementById('runtime-trend-max'),
        average: document.getElementById('runtime-trend-avg'),
        count: document.getElementById('runtime-trend-count'),
        refresh: document.getElementById('runtime-trend-refresh'),
    };

    const trendUrl = tagId => config.trendUrlTemplate.replace(
        '__TAG_ID__',
        String(tagId)
    );

    const formatTrendValue = (value, tag) => {
        if (value === null || value === undefined || Number.isNaN(Number(value))) {
            return '--';
        }

        const decimals = Math.min(
            Math.max(Number(tag?.decimals ?? 1), 0),
            6
        );

        return `${Number(value).toFixed(decimals)}${tag?.unit ? ` ${tag.unit}` : ''}`;
    };

    const setTrendLoading = loading => {
        trendElements.loading?.classList.toggle('d-none', !loading);
        trendElements.refresh?.toggleAttribute('disabled', loading);
    };

    const showTrendMessage = (message = '', type = 'danger') => {
        if (!trendElements.message) return;

        trendElements.message.innerHTML = message
            ? `<div class="alert alert-${type} py-2">${escapeHtml(message)}</div>`
            : '';
    };

    const openTrend = tagId => {
        trendState.tagId = Number(tagId);
        trendState.range = '1h';

        document.querySelectorAll('.runtime-trend-range').forEach(button => {
            button.classList.toggle('active', button.dataset.range === '1h');
        });

        if (
            trendElements.modal
            && window.bootstrap?.Modal
        ) {
            trendState.modal = window.bootstrap.Modal.getOrCreateInstance(
                trendElements.modal
            );
            trendState.modal.show();
        }

        loadTrend();
    };

    const loadTrend = async () => {
        if (!trendState.tagId) return;

        setTrendLoading(true);
        showTrendMessage('');

        try {
            const url = new URL(
                trendUrl(trendState.tagId),
                window.location.origin
            );
            url.searchParams.set('range', trendState.range);

            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
                cache: 'no-store',
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(
                    result.message
                    || Object.values(result.errors || {}).flat()[0]
                    || `HTTP ${response.status}`
                );
            }

            trendState.data = result;
            renderTrend(result);
        } catch (error) {
            trendState.data = null;
            clearTrendChart();
            showTrendMessage(
                error.message || 'Không tải được dữ liệu Trend.'
            );
        } finally {
            setTrendLoading(false);
        }
    };

    const renderTrend = data => {
        const {tag, statistics, range, points} = data;

        trendElements.title.textContent = `Trend — ${tag.name}`;
        trendElements.subtitle.textContent = [
            tag.address || '',
            range.label || '',
            `${points.length} điểm`,
        ].filter(Boolean).join(' • ');

        trendElements.current.textContent =
            formatTrendValue(statistics.current, tag);
        trendElements.minimum.textContent =
            formatTrendValue(statistics.minimum, tag);
        trendElements.maximum.textContent =
            formatTrendValue(statistics.maximum, tag);
        trendElements.average.textContent =
            formatTrendValue(statistics.average, tag);
        trendElements.count.textContent =
            String(statistics.count || 0);

        drawTrendChart(data);
    };

    const clearTrendChart = () => {
        if (!trendElements.chart) return;

        trendElements.chart.innerHTML = `
            <text x="550"
                  y="215"
                  text-anchor="middle"
                  class="runtime-trend-empty-text">
                Không có dữ liệu lịch sử trong khoảng đã chọn
            </text>
        `;
    };

    const svgElement = (name, attributes = {}) => {
        const element = document.createElementNS(
            'http://www.w3.org/2000/svg',
            name
        );

        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, String(value));
        });

        return element;
    };

    const drawTrendChart = data => {
        const svg = trendElements.chart;
        const points = data.points || [];

        if (!svg || points.length < 2) {
            clearTrendChart();
            return;
        }

        svg.innerHTML = '';

        const width = 1100;
        const height = 430;
        const padding = {
            top: 25,
            right: 35,
            bottom: 55,
            left: 75,
        };

        const chartWidth = width - padding.left - padding.right;
        const chartHeight = height - padding.top - padding.bottom;

        const timestamps = points.map(point =>
            new Date(point.time).getTime()
        );
        const values = points.map(point => Number(point.value));

        const limitValues = [
            data.tag.warning_low,
            data.tag.warning_high,
        ].filter(value => value !== null && value !== undefined);

        let minimum = Math.min(...values, ...limitValues);
        let maximum = Math.max(...values, ...limitValues);

        if (minimum === maximum) {
            minimum -= 1;
            maximum += 1;
        }

        const margin = (maximum - minimum) * 0.08;
        minimum -= margin;
        maximum += margin;

        const minTime = Math.min(...timestamps);
        const maxTime = Math.max(...timestamps);

        const x = timestamp =>
            padding.left
            + (
                (timestamp - minTime)
                / Math.max(maxTime - minTime, 1)
            ) * chartWidth;

        const y = value =>
            padding.top
            + chartHeight
            - (
                (value - minimum)
                / Math.max(maximum - minimum, 0.000001)
            ) * chartHeight;

        const gridGroup = svgElement('g', {
            class: 'runtime-trend-grid',
        });

        for (let index = 0; index <= 5; index += 1) {
            const ratio = index / 5;
            const lineY = padding.top + chartHeight * ratio;
            const value = maximum - (maximum - minimum) * ratio;

            gridGroup.appendChild(svgElement('line', {
                x1: padding.left,
                y1: lineY,
                x2: width - padding.right,
                y2: lineY,
            }));

            const label = svgElement('text', {
                x: padding.left - 10,
                y: lineY + 4,
                'text-anchor': 'end',
            });
            label.textContent = value.toFixed(data.tag.decimals ?? 1);
            gridGroup.appendChild(label);
        }

        for (let index = 0; index <= 6; index += 1) {
            const ratio = index / 6;
            const lineX = padding.left + chartWidth * ratio;
            const timestamp = minTime + (maxTime - minTime) * ratio;

            gridGroup.appendChild(svgElement('line', {
                x1: lineX,
                y1: padding.top,
                x2: lineX,
                y2: padding.top + chartHeight,
            }));

            const label = svgElement('text', {
                x: lineX,
                y: height - 22,
                'text-anchor': 'middle',
            });
            label.textContent = new Date(timestamp).toLocaleString(
                'vi-VN',
                {
                    hour: '2-digit',
                    minute: '2-digit',
                    day: trendState.range === '24h' ? '2-digit' : undefined,
                    month: trendState.range === '24h' ? '2-digit' : undefined,
                }
            );
            gridGroup.appendChild(label);
        }

        svg.appendChild(gridGroup);

        const drawLimit = (value, className, labelText) => {
            if (value === null || value === undefined) return;

            const lineY = y(Number(value));

            svg.appendChild(svgElement('line', {
                x1: padding.left,
                y1: lineY,
                x2: width - padding.right,
                y2: lineY,
                class: className,
            }));

            const label = svgElement('text', {
                x: width - padding.right - 5,
                y: lineY - 5,
                'text-anchor': 'end',
                class: `${className}-label`,
            });
            label.textContent = `${labelText}: ${formatTrendValue(value, data.tag)}`;
            svg.appendChild(label);
        };

        drawLimit(
            data.tag.warning_high,
            'runtime-trend-limit-high',
            'Cao'
        );
        drawLimit(
            data.tag.warning_low,
            'runtime-trend-limit-low',
            'Thấp'
        );

        const pathData = points.map((point, index) => {
            const command = index === 0 ? 'M' : 'L';
            return `${command} ${x(timestamps[index])} ${y(values[index])}`;
        }).join(' ');

        svg.appendChild(svgElement('path', {
            d: pathData,
            class: 'runtime-trend-value-path',
        }));

        const interaction = svgElement('rect', {
            x: padding.left,
            y: padding.top,
            width: chartWidth,
            height: chartHeight,
            class: 'runtime-trend-interaction',
        });

        const hoverLine = svgElement('line', {
            y1: padding.top,
            y2: padding.top + chartHeight,
            class: 'runtime-trend-hover-line d-none',
        });

        const hoverPoint = svgElement('circle', {
            r: 5,
            class: 'runtime-trend-hover-point d-none',
        });

        svg.appendChild(hoverLine);
        svg.appendChild(hoverPoint);
        svg.appendChild(interaction);

        interaction.addEventListener('mousemove', event => {
            const rect = svg.getBoundingClientRect();
            const mouseX = (
                (event.clientX - rect.left)
                / rect.width
            ) * width;

            const targetTime = minTime
                + (
                    (mouseX - padding.left)
                    / chartWidth
                ) * (maxTime - minTime);

            let nearestIndex = 0;
            let nearestDistance = Infinity;

            timestamps.forEach((timestamp, index) => {
                const distance = Math.abs(timestamp - targetTime);

                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestIndex = index;
                }
            });

            const pointX = x(timestamps[nearestIndex]);
            const pointY = y(values[nearestIndex]);

            hoverLine.setAttribute('x1', pointX);
            hoverLine.setAttribute('x2', pointX);
            hoverLine.classList.remove('d-none');

            hoverPoint.setAttribute('cx', pointX);
            hoverPoint.setAttribute('cy', pointY);
            hoverPoint.classList.remove('d-none');

            trendElements.tooltip.innerHTML = `
                <strong>${escapeHtml(formatTrendValue(values[nearestIndex], data.tag))}</strong>
                <div>${escapeHtml(new Date(timestamps[nearestIndex]).toLocaleString('vi-VN'))}</div>
            `;
            trendElements.tooltip.classList.remove('d-none');

            const shell = trendElements.tooltip.parentElement;
            const shellRect = shell.getBoundingClientRect();

            trendElements.tooltip.style.left = `${Math.min(
                Math.max(event.clientX - shellRect.left + 12, 8),
                shellRect.width - 180
            )}px`;
            trendElements.tooltip.style.top = `${Math.max(
                event.clientY - shellRect.top - 55,
                8
            )}px`;
        });

        interaction.addEventListener('mouseleave', () => {
            hoverLine.classList.add('d-none');
            hoverPoint.classList.add('d-none');
            trendElements.tooltip.classList.add('d-none');
        });
    };

    document.addEventListener('click', event => {
        const card = event.target.closest(
            '.runtime-tag-card[data-chart-enabled="1"]'
        );

        if (card) {
            openTrend(card.dataset.tagId);
            return;
        }

        const rangeButton = event.target.closest('.runtime-trend-range');

        if (rangeButton) {
            trendState.range = rangeButton.dataset.range;

            document.querySelectorAll('.runtime-trend-range').forEach(button => {
                button.classList.toggle(
                    'active',
                    button === rangeButton
                );
            });

            loadTrend();
        }
    });

    document.addEventListener('keydown', event => {
        const card = event.target.closest(
            '.runtime-tag-card[data-chart-enabled="1"]'
        );

        if (
            card
            && ['Enter', ' '].includes(event.key)
        ) {
            event.preventDefault();
            openTrend(card.dataset.tagId);
        }
    });

    trendElements.refresh?.addEventListener('click', loadTrend);

    renderLayout();
    setConnectionState({
        code: 'delayed',
        label: 'Đang chờ dữ liệu mới...',
    }, config.layout.generated_at);
    refreshData();

    window.setInterval(
        refreshData,
        Number(config.pollInterval || 5000)
    );
})();
