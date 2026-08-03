(() => {
    'use strict';

    const config = window.m62TechnologyConfig;
    if (!config) return;

    const refreshSeconds = Math.max(1, Math.round(config.refreshInterval / 1000));
    let countdown = refreshSeconds;
    let loading = false;

    const usedTagIds = new Set();

    const zoneAliases = {
        CL: ['cl'], XL1: ['xl1'], XL2: ['xl2'], XL3: ['xl3'],
        XL4: ['xl4'], XL5: ['xl5'], CN: ['cn'],
        XLMC1: ['xlmc1'], XLMC2: ['xlmc2'], XLMC3: ['xlmc3'],
        XLMC4: ['xlmc4'], DH1: ['dh1', 'đh1'], DH2: ['dh2', 'đh2'],
        DH3: ['dh3', 'đh3'], DH4: ['dh4', 'đh4'],
        DH5: ['dh5', 'đh5'], DH6: ['dh6', 'đh6']
    };

    const metricAliases = {
        screw_speed: ['tốc độ vx', 'tốc độ vít', 'screw speed'],
        screw_load: ['tải vx', 'tải vít', 'screw load'],
        extruder_balance: ['cân thực tế', 'định lượng thực tế'],
        melt_pressure: ['áp lực nhựa', 'áp suất nhựa'],
        specific_consumption: ['suất tiêu hao'],
        thread_speed: ['tốc độ máy chỉ'],
        thread_load: ['tải máy chỉ'],
        puller_speed: ['tốc độ dàn kéo', 'tốc độ kéo'],
        weight_per_meter: ['định lượng g/m tt', 'định lượng g/m'],
        ck1_temperature: ['nhiệt độ ck1'],
        set_weight: ['định lượng đặt', 'định lượng đặt g/m'],
        ck1_pressure: ['áp suất bể ck1'],
        set_balance: ['cài đặt cân định lượng'],
        productivity: ['năng suất', 'năng suất kg/h'],
        melt_temperature: ['nhiệt độ nhựa'],
        vacuum: ['vacuum', 'chân không'],
        line_speed: ['tốc độ dây chuyền', 'line speed']
    };

    const productionAliases = {
        production_date: ['ngày sản xuất', 'ngày'],
        shift: ['ca sản xuất', 'ca'],
        team: ['tổ sản xuất', 'tổ'],
        product_code: ['mã sản phẩm', 'sản phẩm'],
        material_type: ['loại nguyên liệu', 'loại nl'],
        diameter: ['đường kính'],
        puller_setting: ['chuốt'],
        material_1: ['nguyên liệu 1', 'nl1'],
        material_2: ['nguyên liệu 2', 'nl2'],
        stop_hours: ['giờ dừng', 'thời gian dừng'],
        heating_hours: ['giờ gia nhiệt', 'thời gian gia nhiệt'],
        running_hours: ['giờ chạy', 'thời gian chạy']
    };

    const normalize = value => String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .replace(/[%_:./\\-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const escapeHtml = value => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const allTags = groups => groups.flatMap(group => group.tags || []);

    const tagSearchText = tag => normalize([
        tag.display_name, tag.source_name, tag.source_key,
        tag.address, tag.display_group
    ].filter(Boolean).join(' '));

    const findTag = (tags, aliases, excludes = []) => {
        const aliasList = aliases.map(normalize);
        const excludeList = excludes.map(normalize);

        return tags.find(tag => {
            const text = tagSearchText(tag);
            return aliasList.some(alias => text === alias || text.includes(alias))
                && !excludeList.some(exclude => text.includes(exclude));
        }) || null;
    };

    const findZoneTag = (tags, zone, kind) => {
        const zones = (zoneAliases[zone] || [zone]).map(normalize);
        const candidates = tags.filter(tag =>
            zones.some(alias => tagSearchText(tag).includes(alias))
        );

        const kinds = kind === 'set'
            ? ['nhiet do dat', 'nhiet do cai dat', 'nhiet do cau hinh', 'set temperature']
            : ['nhiet do thuc te', 'actual temperature'];

        let tag = candidates.find(item =>
            kinds.some(alias => tagSearchText(item).includes(normalize(alias)))
        );

        if (!tag && kind === 'actual') {
            tag = candidates.find(item => {
                const text = tagSearchText(item);
                return text.includes('nhiet do')
                    && !text.includes('nhiet do dat')
                    && !text.includes('cai dat')
                    && !text.includes('cau hinh');
            });
        }

        return tag || null;
    };

    const stateClass = state => `state-${String(state || 'NO_DATA').toLowerCase()}`;

    const setValueElement = (element, tag) => {
        element.className = element.className
            .split(' ')
            .filter(name => !name.startsWith('state-'))
            .join(' ');

        if (!tag) {
            element.textContent = '—';
            element.classList.add('state-no_data');
            element.title = 'Chưa tìm thấy tag tương ứng';
            return;
        }

        usedTagIds.add(tag.id);
        element.textContent = tag.formatted_value ?? '—';
        element.classList.add(stateClass(tag.alarm_state));
        element.title = [
            tag.display_name || tag.source_name || '',
            tag.address || tag.source_key || '',
            tag.collected_at || ''
        ].filter(Boolean).join(' | ');
    };

    const renderZones = tags => {
        document.querySelectorAll('[data-zone][data-kind]').forEach(element => {
            const tag = findZoneTag(tags, element.dataset.zone, element.dataset.kind);
            setValueElement(element, tag);
        });

        document.querySelectorAll('[data-zone-header]').forEach(header => {
            const zone = header.dataset.zoneHeader;
            const available = findZoneTag(tags, zone, 'set')
                || findZoneTag(tags, zone, 'actual');
            header.classList.toggle('zone-disabled', !available);
        });
    };

    const renderAliasFields = (tags, selector, aliasesMap) => {
        document.querySelectorAll(selector).forEach(element => {
            const key = element.dataset.metric || element.dataset.production;
            let excludes = [];

            if (key === 'set_weight') excludes = ['thuc te', 'tt'];
            if (key === 'weight_per_meter') excludes = ['dat', 'cai dat'];

            const tag = findTag(tags, aliasesMap[key] || [], excludes);
            setValueElement(element, tag);
        });
    };

    const renderSystem = data => {
        document.getElementById('machine-status-value').textContent =
            data.machine?.status_label || data.machine?.status || '—';

        document.getElementById('machine-last-seen').textContent =
            formatDateTime(data.machine?.last_seen_at);

        document.getElementById('machine-total-tags').textContent =
            data.collector?.total_tags ?? '—';
    };

    const renderExtraTags = (groups, tags) => {
        const remaining = tags.filter(tag => !usedTagIds.has(tag.id));
        document.getElementById('extra-tag-count').textContent =
            `${remaining.length} tag`;

        if (!remaining.length) {
            document.getElementById('extra-tag-groups').innerHTML =
                '<div class="text-muted py-3">Không còn tag chưa hiển thị.</div>';
            return;
        }

        const grouped = remaining.reduce((result, tag) => {
            const group = tag.display_group || 'Chưa phân nhóm';
            (result[group] ||= []).push(tag);
            return result;
        }, {});

        document.getElementById('extra-tag-groups').innerHTML =
            Object.entries(grouped).map(([groupName, groupTags]) => `
                <div class="extra-group">
                    <div class="extra-group-title">
                        <span>${escapeHtml(groupName)}</span>
                        <span>${groupTags.length} tag</span>
                    </div>
                    <div class="extra-grid">
                        ${groupTags.map(tag => `
                            <div class="extra-tag-item">
                                <div class="extra-tag-label"
                                     title="${escapeHtml(tag.display_name || tag.source_name)}">
                                    ${escapeHtml(tag.display_name || tag.source_name)}
                                </div>
                                <div class="extra-tag-value ${stateClass(tag.alarm_state)}"
                                     title="${escapeHtml(tag.address || tag.source_key || '')}">
                                    ${escapeHtml(tag.formatted_value ?? '—')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');
    };

    const formatDateTime = value => {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;

        return new Intl.DateTimeFormat('vi-VN', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        }).format(date);
    };

    const renderRealtimeStatus = data => {
        const message = document.getElementById('realtime-message');
        const status = data.machine?.status || 'OFFLINE';

        message.classList.remove('alert-info', 'alert-warning', 'alert-danger');
        message.classList.add(
            status === 'ONLINE' ? 'alert-info'
                : status === 'WARNING' ? 'alert-warning'
                : 'alert-danger'
        );

        document.getElementById('last-update-text').textContent =
            `- Cập nhật lúc: ${formatDateTime(data.machine?.last_seen_at)}`;
    };

    const showError = message => {
        document.getElementById('monitoring-error').innerHTML = `
            <div class="alert alert-danger py-2">${escapeHtml(message)}</div>
        `;
    };

    const loadData = async () => {
        if (loading) return;
        loading = true;
        usedTagIds.clear();

        try {
            const response = await fetch(config.apiUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không tải được dữ liệu giám sát.');
            }

            const groups = result.data.groups || [];
            const tags = allTags(groups);

            renderRealtimeStatus(result.data);
            renderZones(tags);
            renderAliasFields(tags, '[data-metric]', metricAliases);
            renderAliasFields(tags, '[data-production]', productionAliases);
            renderSystem(result.data);
            renderExtraTags(groups, tags);

            document.getElementById('monitoring-error').innerHTML = '';
            countdown = refreshSeconds;
        } catch (error) {
            showError(error.message);
        } finally {
            loading = false;
        }
    };

    document.getElementById('realtime-button')
        ?.addEventListener('click', loadData);

    loadData();

    window.setInterval(loadData, config.refreshInterval);
    window.setInterval(() => {
        countdown = countdown <= 1 ? refreshSeconds : countdown - 1;
        document.getElementById('countdown').textContent = countdown;
    }, 1000);
})();
