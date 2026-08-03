@php
    $isEdit = isset($declaration);
    $formAction = $isEdit
        ? route('production-declarations.update', $declaration)
        : route('production-declarations.store');
    $draftStorageKey = $isEdit
        ? 'ntp_plc_production_entry_edit_' . $declaration->id
        : 'ntp_plc_production_entry_create';
@endphp

@push('styles')
<link href="{{ asset('vendor/select2/css/select2.min.css') }}" rel="stylesheet">
<style>
    .production-entry-page {
        --entry-border: #dfe5ec;
        --entry-soft: #f7f9fc;
        padding-bottom: 88px;
    }

    .entry-section {
        border: 1px solid var(--entry-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .05);
        margin-bottom: 14px;
        overflow: hidden;
    }

    .entry-section-title {
        padding: 12px 14px;
        background: var(--entry-soft);
        border-bottom: 1px solid var(--entry-border);
        font-weight: 700;
    }

    .entry-section-body {
        padding: 14px;
    }

    .product-card {
        border: 1px solid var(--entry-border);
        border-radius: 14px;
        background: #fff;
        margin-bottom: 14px;
        overflow: hidden;
    }

    .product-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        background: #f8fafc;
        border-bottom: 1px solid var(--entry-border);
    }

    .product-card-body {
        padding: 12px;
    }

    .field-label {
        font-size: .78rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
    }

    .quantity-input {
        font-size: 1.05rem;
        font-weight: 600;
        min-height: 46px;
    }

    .product-summary {
        border-radius: 10px;
        background: #eff6ff;
        color: #1e3a8a;
        padding: 8px 10px;
        font-size: .82rem;
        margin-top: 8px;
    }

    .mobile-action-bar {
        position: fixed;
        left: var(--sidebar-width, 260px);
        right: 0;
        bottom: 0;
        z-index: 1025;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 10px 18px;
        background: rgba(255,255,255,.96);
        border-top: 1px solid #dfe5ec;
        backdrop-filter: blur(8px);
    }

    .autosave-indicator {
        font-size: .78rem;
        color: #64748b;
        margin-right: auto;
        align-self: center;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 46px;
        border-color: #ced4da;
        border-radius: .5rem;
        display: flex;
        align-items: center;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: normal;
        padding-left: 12px;
        padding-right: 34px;
        white-space: normal;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 44px;
    }

    .select2-dropdown {
        z-index: 1100;
    }

    .downtime-row {
        border: 1px solid var(--entry-border);
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 10px;
    }

    @media (max-width: 991.98px) {
        .production-entry-page {
            padding-bottom: 118px;
        }

        .mobile-action-bar {
            left: 0;
            padding: 9px 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .mobile-action-bar .autosave-indicator {
            grid-column: 1 / -1;
            margin: 0;
            text-align: center;
        }

        .mobile-action-bar .btn-cancel {
            display: none;
        }

        .entry-section-body {
            padding: 10px;
        }

        .product-card-body {
            padding: 10px;
        }

        input,
        select,
        textarea,
        .select2-container .select2-selection--single {
            font-size: 16px !important;
        }
    }

    @media (min-width: 992px) {
        .product-card-body .desktop-grid {
            display: grid;
            grid-template-columns: minmax(280px, 2.3fr) repeat(5, minmax(115px, 1fr));
            gap: 10px;
            align-items: end;
        }

        .product-card-body .desktop-grid-secondary {
            display: grid;
            grid-template-columns: 160px 180px minmax(220px, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
    }
</style>
@endpush

<form method="POST" action="{{ $formAction }}" id="declarationForm" class="production-entry-page">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="entry-section">
        <div class="entry-section-title">
            <i class="bi bi-calendar2-week me-1"></i>Thông tin ca sản xuất
        </div>
        <div class="entry-section-body">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label fw-semibold">Máy <span class="text-danger">*</span></label>
                    <select name="machine_id" id="machineId"
                            class="form-select select2-basic @error('machine_id') is-invalid @enderror"
                            data-placeholder="Tìm và chọn máy" required>
                        <option value=""></option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}"
                                @selected(old('machine_id', $isEdit ? $declaration->machine_id : null) == $machine->id)>
                                {{ $machine->code }} - {{ $machine->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('machine_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold">Ngày sản xuất <span class="text-danger">*</span></label>
                    <input type="date" name="production_date" id="productionDate"
                           value="{{ old('production_date', $isEdit ? $declaration->production_date->toDateString() : now()->toDateString()) }}"
                           class="form-control @error('production_date') is-invalid @enderror" required>
                    @error('production_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold">Ca <span class="text-danger">*</span></label>
                    <select name="production_shift_id" id="productionShiftId"
                            class="form-select select2-basic @error('production_shift_id') is-invalid @enderror"
                            data-placeholder="Chọn ca" required>
                        <option value=""></option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}"
                                @selected(old('production_shift_id', $isEdit ? $declaration->production_shift_id : null) == $shift->id)>
                                {{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    @error('production_shift_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-lg-2">
                    <label class="form-label fw-semibold">Dừng kế hoạch (phút)</label>
                    <input type="number" inputmode="decimal" step="0.01" min="0"
                           name="planned_downtime_minutes"
                           value="{{ old('planned_downtime_minutes', $isEdit ? $declaration->planned_downtime_minutes : 0) }}"
                           class="form-control">
                </div>
            </div>

            <div id="duplicateWarning"
                 class="alert alert-warning mt-3 mb-0 d-none"
                 role="alert">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">Cảnh báo trùng phiếu</div>
                        <div id="duplicateWarningMessage"></div>
                        <a id="duplicateWarningLink"
                           href="#"
                           class="btn btn-sm btn-outline-dark mt-2"
                           target="_blank">
                            Mở phiếu đang tồn tại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="entry-section">
        <div class="entry-section-title d-flex justify-content-between align-items-center gap-2">
            <div>
                <i class="bi bi-box-seam me-1"></i>Chi tiết sản phẩm
                <div class="small text-muted fw-normal">
                    Select2 hỗ trợ tìm nhanh theo mã hoặc tên sản phẩm.
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="addProductRow">
                <i class="bi bi-plus-lg me-1"></i>Thêm
            </button>
        </div>
        <div class="entry-section-body">
            @error('items')<div class="alert alert-danger">{{ $message }}</div>@enderror
            <div id="productRows"></div>

            <div class="d-flex justify-content-between align-items-center rounded bg-light p-3 mt-2">
                <span class="fw-semibold">Tổng khối lượng quy đổi</span>
                <strong class="text-primary fs-5"><span id="grandTotalKg">0.000</span> kg</strong>
            </div>

            <div class="alert alert-info small mt-3 mb-0">
                Tổng sản lượng, Chính phẩm và Phế phẩm nhập theo cây/cuộn/ĐVT.
                Phế liệu nhập riêng bằng kg. Hệ thống không tự thay Tổng sản lượng bằng phép cộng khác.
            </div>
        </div>
    </div>

    <div class="entry-section">
        <div class="entry-section-title">
            <i class="bi bi-stopwatch me-1"></i>Nguyên nhân dừng trong ca
        </div>
        <div class="entry-section-body">
            @foreach($reasons as $index => $reason)
                @php
                    $savedDowntime = collect($initialDowntimes)->firstWhere('downtime_reason_id', $reason->id);
                @endphp
                <div class="downtime-row">
                    <input type="hidden"
                           name="downtimes[{{ $index }}][downtime_reason_id]"
                           value="{{ $reason->id }}">
                    <div class="fw-semibold mb-2">
                        {{ $reason->code }} - {{ $reason->name }}
                        @if($reason->is_planned)
                            <span class="badge text-bg-secondary">Kế hoạch</span>
                        @endif
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-sm-4">
                            <label class="field-label">Số phút</label>
                            <input type="number" inputmode="decimal" min="0" step="0.01"
                                   name="downtimes[{{ $index }}][minutes]"
                                   value="{{ data_get($savedDowntime, 'minutes') }}"
                                   class="form-control">
                        </div>
                        <div class="col-12 col-sm-8">
                            <label class="field-label">Ghi chú</label>
                            <input name="downtimes[{{ $index }}][note]"
                                   value="{{ data_get($savedDowntime, 'note') }}"
                                   class="form-control"
                                   placeholder="Mô tả thêm nếu cần">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="entry-section">
        <div class="entry-section-title">
            <i class="bi bi-card-text me-1"></i>Ghi chú ca
        </div>
        <div class="entry-section-body">
            <textarea name="note" rows="3" class="form-control"
                      placeholder="Ghi chú chung của ca sản xuất">{{ old('note', $isEdit ? $declaration->note : null) }}</textarea>
        </div>
    </div>

    <div class="mobile-action-bar">
        <div class="autosave-indicator" id="autosaveIndicator">
            <i class="bi bi-phone me-1"></i>Dữ liệu đang được lưu tạm trên thiết bị
        </div>
        <a href="{{ route('production-declarations.index') }}"
           class="btn btn-outline-secondary btn-cancel">Hủy</a>
        <button type="submit" name="submit_action" value="draft"
                class="btn btn-outline-primary">
            <i class="bi bi-save me-1"></i>Lưu nháp
        </button>
        <button type="submit" name="submit_action" value="submit"
                class="btn btn-primary">
            <i class="bi bi-send-check me-1"></i>Gửi xác nhận
        </button>
    </div>
</form>

@push('scripts')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('vendor/select2/js/i18n/vi.js') }}"></script>
<script>
(() => {
    const productSearchUrl = @json(route('production-declarations.product-search'));
    const duplicateCheckUrl = @json(route('production-declarations.duplicate-check'));
    const currentDeclarationId = @json($isEdit ? $declaration->id : null);
    const initialItems = @json($initialItems);
    const storageKey = @json($draftStorageKey);
    const isEdit = @json($isEdit);
    const form = document.getElementById('declarationForm');
    const rowsContainer = document.getElementById('productRows');
    const machine = document.getElementById('machineId');
    const productionDate = document.getElementById('productionDate');
    const productionShift = document.getElementById('productionShiftId');
    const duplicateWarning = document.getElementById('duplicateWarning');
    const duplicateWarningMessage = document.getElementById('duplicateWarningMessage');
    const duplicateWarningLink = document.getElementById('duplicateWarningLink');
    const addButton = document.getElementById('addProductRow');
    const autosaveIndicator = document.getElementById('autosaveIndicator');

    const productCache = new Map();
    let rowIndex = 0;
    let restoredDraft = null;
    let saveTimer = null;

    const numberValue = value => Number(value || 0);
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const initializeSelect2 = element => {
        const $element = window.jQuery(element);
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }

        $element.select2({
            width: '100%',
            placeholder: element.dataset.placeholder || 'Chọn',
            allowClear: true,
            language: {
                noResults: () => 'Không tìm thấy dữ liệu',
                searching: () => 'Đang tìm...',
                inputTooShort: () => 'Nhập thêm từ khóa'
            }
        });
    };

    document.querySelectorAll('.select2-basic').forEach(initializeSelect2);

    const selectedOptionHtml = value => {
        if (!value?.product_id) return '<option value=""></option>';
        const text = value.product_text || `Sản phẩm #${value.product_id}`;
        return `<option value="${value.product_id}" selected>${escapeHtml(text)}</option>`;
    };


    const refreshGrandTotal = () => {
        const total = [...rowsContainer.querySelectorAll('.row-total-kg')]
            .reduce((sum, element) => sum + numberValue(element.value), 0);

        document.getElementById('grandTotalKg').textContent = total.toFixed(3);
    };

    const calculateRow = card => {
        const select = card.querySelector('.product-select');
        const product = productCache.get(String(select.value));
        const totalQuantity = numberValue(card.querySelector('.total-quantity').value);
        const actualLengthM = numberValue(card.querySelector('.actual-length').value);
        const standardWeight = numberValue(product?.standard_weight_g);
        const unit = String(product?.unit || '')
            .trim()
            .toLowerCase()
            .replaceAll(' ', '')
            .replaceAll('.', '');

        let totalKg = 0;
        if (['kg', 'kilogram', 'kilôgam'].includes(unit)) {
            totalKg = totalQuantity;
        } else if (['t', 'tan', 'tấn', 'ton', 'tonne'].includes(unit)) {
            totalKg = totalQuantity * 1000;
        } else if (['m', 'met', 'mét', 'meter', 'metre'].includes(unit)) {
            totalKg = totalQuantity * actualLengthM * standardWeight / 1000;
        } else if (standardWeight > 0) {
            totalKg = totalQuantity * standardWeight / 1000;
        }

        card.querySelector('.row-total-kg').value = totalKg.toFixed(3);

        const info = card.querySelector('.product-info');
        if (product) {
            info.innerHTML = `
                <strong>${escapeHtml(product.code)}</strong> ·
                ${escapeHtml(product.unit)} ·
                ${numberValue(product.standard_weight_g).toLocaleString('vi-VN')}
                ${['m', 'met', 'mét', 'meter', 'metre'].includes(unit) ? 'g/m' : 'g/ĐVT'} ·
                Định mức ${numberValue(product.standard_rate).toLocaleString('vi-VN')}
                ${escapeHtml(product.rate_unit)}
            `;
        } else {
            info.textContent = 'Chưa chọn sản phẩm';
        }

        refreshGrandTotal();
        scheduleSave();
    };

    const addRow = (value = {}) => {
        const index = rowIndex++;
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <div class="product-card-header">
                <div class="fw-semibold">
                    <i class="bi bi-box me-1"></i>Sản phẩm <span class="product-number">${index + 1}</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="product-card-body">
                <div class="desktop-grid">
                    <div>
                        <label class="field-label">Sản phẩm *</label>
                        <select name="items[${index}][product_id]"
                                class="form-select product-select"
                                data-placeholder="Nhập mã hoặc tên sản phẩm"
                                required>
                            <option value=""></option>
                            ${selectedOptionHtml(value)}
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Chiều dài thực tế (m)</label>
                        <input type="number" inputmode="decimal" step="0.0001" min="0"
                               name="items[${index}][actual_length_m]"
                               class="form-control quantity-input actual-length"
                               value="${escapeHtml(value.actual_length_m ?? '')}">
                    </div>
                    <div>
                        <label class="field-label">Tổng sản lượng *</label>
                        <input type="number" inputmode="decimal" step="0.001" min="0"
                               name="items[${index}][total_quantity]"
                               class="form-control quantity-input total-quantity"
                               value="${escapeHtml(value.total_quantity ?? 0)}" required>
                    </div>
                    <div>
                        <label class="field-label">Chính phẩm *</label>
                        <input type="number" inputmode="decimal" step="0.001" min="0"
                               name="items[${index}][good_quantity]"
                               class="form-control quantity-input good-quantity"
                               value="${escapeHtml(value.good_quantity ?? 0)}" required>
                    </div>
                    <div>
                        <label class="field-label">Phế phẩm</label>
                        <input type="number" inputmode="decimal" step="0.001" min="0"
                               name="items[${index}][reject_quantity]"
                               class="form-control quantity-input reject-quantity"
                               value="${escapeHtml(value.reject_quantity ?? 0)}">
                    </div>
                    <div>
                        <label class="field-label">Phế liệu (kg)</label>
                        <input type="number" inputmode="decimal" step="0.001" min="0"
                               name="items[${index}][scrap_quantity]"
                               class="form-control quantity-input scrap-quantity"
                               value="${escapeHtml(value.scrap_quantity ?? 0)}">
                    </div>
                </div>

                <div class="product-summary product-info">Chưa chọn sản phẩm</div>

                <div class="desktop-grid-secondary">
                    <div>
                        <label class="field-label">Quy đổi tổng (kg)</label>
                        <input class="form-control row-total-kg bg-light" value="0.000" readonly>
                    </div>
                    <div>
                        <label class="field-label">Lệnh sản xuất</label>
                        <input name="items[${index}][work_order]"
                               class="form-control"
                               value="${escapeHtml(value.work_order ?? '')}">
                    </div>
                    <div>
                        <label class="field-label">Ghi chú sản phẩm</label>
                        <input name="items[${index}][note]"
                               class="form-control"
                               value="${escapeHtml(value.note ?? '')}">
                    </div>
                </div>
            </div>
        `;

        rowsContainer.appendChild(card);

        const productSelect = card.querySelector('.product-select');
        const $productSelect = window.jQuery(productSelect);

        $productSelect.select2({
            width: '100%',
            language: {
                noResults: () => 'Không tìm thấy sản phẩm có định mức cho máy và ngày đã chọn',
                searching: () => 'Đang tìm sản phẩm...',
                loadingMore: () => 'Đang tải thêm...'
            },
            placeholder: 'Nhập mã hoặc tên sản phẩm',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: productSearchUrl,
                dataType: 'json',
                delay: 250,
                cache: true,
                data: params => ({
                    machine_id: machine.value,
                    production_date: productionDate.value,
                    q: params.term || '',
                    page: params.page || 1
                }),
                processResults: response => {
                    const results = response.results || [];
                    results.forEach(item => productCache.set(String(item.id), item));

                    if (!results.length && response.meta) {
                        console.info('Không có sản phẩm phù hợp', response.meta);
                    }

                    return {
                        results,
                        pagination: response.pagination || {more: false}
                    };
                },
                error: xhr => {
                    let message = 'Không tải được danh sách sản phẩm.';

                    if (xhr.status === 403) {
                        message = 'Tài khoản chưa có quyền tìm sản phẩm trong phiếu nhập.';
                    } else if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};
                        message = Object.values(errors).flat().join(' ');
                    } else if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    }

                    console.error('Lỗi tìm sản phẩm Select2:', xhr.status, xhr.responseText);
                    autosaveIndicator.innerHTML =
                        `<i class="bi bi-exclamation-triangle text-danger me-1"></i>${escapeHtml(message)}`;
                }
            },
            templateResult: item => {
                if (item.loading) return item.text;
                const code = escapeHtml(item.code || '');
                const name = escapeHtml(item.name || item.text || '');
                const unit = escapeHtml(item.unit || '');
                return window.jQuery(`<div><strong>${code}</strong><div class="small text-muted">${name} · ${unit}</div></div>`);
            },
            templateSelection: item => item.text || 'Chọn sản phẩm'
        });

        if (value.product_id) {
            const initialProduct = {
                id: value.product_id,
                text: value.product_text || `Sản phẩm #${value.product_id}`,
                code: value.product_code || '',
                name: value.product_name || '',
                unit: value.unit || '',
                standard_weight_g: value.standard_weight_g || 0,
                default_length_m: value.default_length_m || 0,
                standard_rate: value.standard_rate || 0,
                rate_unit: value.rate_unit || ''
            };
            productCache.set(String(value.product_id), initialProduct);
        }

        const updateProduct = event => {
            const selectedData = event?.params?.data;
            if (selectedData?.id) productCache.set(String(selectedData.id), selectedData);
            const product = productCache.get(String(productSelect.value));

            if (product) {
                const lengthInput = card.querySelector('.actual-length');
                if (!lengthInput.value && numberValue(product.default_length_m) > 0) {
                    lengthInput.value = product.default_length_m;
                }
            }

            calculateRow(card);
        };

        $productSelect.on('select2:select select2:clear change', updateProduct);

        card.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => {
                calculateRow(card);
                scheduleSave();
            });
        });

        card.querySelector('.remove-row').addEventListener('click', () => {
            if (rowsContainer.children.length === 1) {
                alert('Phiếu phải có ít nhất một sản phẩm.');
                return;
            }

            window.jQuery(productSelect).select2('destroy');
            card.remove();
            renumberCards();
            refreshGrandTotal();
            scheduleSave();
        });

        updateProduct();
    };

    const renumberCards = () => {
        [...rowsContainer.querySelectorAll('.product-card')].forEach((card, index) => {
            card.querySelector('.product-number').textContent = index + 1;
        });
    };

    const serializeForm = () => {
        const data = {};
        new FormData(form).forEach((value, key) => {
            if (key === '_token' || key === '_method' || key === 'submit_action') return;

            if (Object.prototype.hasOwnProperty.call(data, key)) {
                data[key] = Array.isArray(data[key])
                    ? [...data[key], value]
                    : [data[key], value];
            } else {
                data[key] = value;
            }
        });

        return {
            savedAt: new Date().toISOString(),
            values: data
        };
    };

    const saveLocalDraft = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(serializeForm()));
            autosaveIndicator.innerHTML =
                `<i class="bi bi-check-circle text-success me-1"></i>Đã lưu tạm lúc ${new Date().toLocaleTimeString('vi-VN')}`;
        } catch (error) {
            autosaveIndicator.innerHTML =
                '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Không thể lưu tạm trên thiết bị';
        }
    };

    const scheduleSave = () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveLocalDraft, 700);
    };

    const restoreSimpleValues = values => {
        Object.entries(values || {}).forEach(([name, value]) => {
            if (name.startsWith('items[') || name.startsWith('downtimes[')) return;

            const element = form.elements.namedItem(name);
            if (!element) return;

            if (element instanceof RadioNodeList) {
                return;
            }

            element.value = Array.isArray(value) ? value[0] : value;
            if (element.tagName === 'SELECT') {
                window.jQuery(element).trigger('change.select2');
            }
        });
    };

    const extractIndexedRows = (values, prefix) => {
        const rows = {};

        Object.entries(values || {}).forEach(([name, value]) => {
            const expression = new RegExp(`^${prefix}\\[(\\d+)\\]\\[([^\\]]+)\\]$`);
            const match = name.match(expression);
            if (!match) return;

            const index = Number(match[1]);
            const field = match[2];
            rows[index] ??= {};
            rows[index][field] = Array.isArray(value) ? value[0] : value;
        });

        return Object.values(rows);
    };

    const loadProducts = async (itemsToLoad = null) => {
        rowsContainer.innerHTML = '';
        rowIndex = 0;
        refreshGrandTotal();

        if (!machine.value || !productionDate.value) return;

        const rows = itemsToLoad?.length ? itemsToLoad : initialItems;
        if (rows.length) {
            rows.forEach(addRow);
        } else {
            addRow();
        }
    };

    const applyLocalDraft = async draft => {
        if (!draft?.values) return;

        restoreSimpleValues(draft.values);
        const localItems = extractIndexedRows(draft.values, 'items');

        await loadProducts(localItems);

        Object.entries(draft.values).forEach(([name, value]) => {
            if (!name.startsWith('downtimes[')) return;
            const element = form.elements.namedItem(name);
            if (element) element.value = Array.isArray(value) ? value[0] : value;
        });

        autosaveIndicator.innerHTML =
            '<i class="bi bi-arrow-counterclockwise text-primary me-1"></i>Đã khôi phục dữ liệu lưu tạm';
    };

    const checkLocalDraft = async () => {
        let draft = null;

        try {
            draft = JSON.parse(localStorage.getItem(storageKey) || 'null');
        } catch (error) {
            localStorage.removeItem(storageKey);
        }

        if (!draft?.savedAt) {
            await loadProducts();
            return;
        }

        const savedTime = new Date(draft.savedAt).toLocaleString('vi-VN');
        const restore = confirm(`Thiết bị có dữ liệu nhập tạm lúc ${savedTime}. Khôi phục dữ liệu này?`);

        if (restore) {
            await applyLocalDraft(draft);
        } else {
            localStorage.removeItem(storageKey);
            await loadProducts();
        }
    };

    window.jQuery(machine).on('change', () => {
        loadProducts();
        scheduleSave();
    });

    productionDate.addEventListener('change', () => {
        loadProducts();
        scheduleSave();
    });

    addButton.addEventListener('click', () => {
        if (!machine.value || !productionDate.value) {
            alert('Vui lòng chọn máy và ngày sản xuất trước.');
            return;
        }
        addRow();
        scheduleSave();
    });

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);
    form.addEventListener('submit', event => {
        if (duplicateFound) {
            event.preventDefault();
            duplicateWarning.scrollIntoView({behavior: 'smooth', block: 'center'});
            alert('Không thể lưu vì đã có phiếu khác cùng Máy - Ngày - Ca. Bạn vẫn có thể thay đổi các trường này để chọn thông tin khác.');
            return;
        }

        localStorage.removeItem(storageKey);
    });

    checkLocalDraft().finally(() => {
        checkDuplicateDeclaration();
    });
})();
</script>
@endpush
