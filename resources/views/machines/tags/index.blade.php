@extends('layouts.app')

@section('title', 'Quản lý tag ' . $machine->code)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Quản lý tag {{ $machine->code }}</h4>
            <div class="text-muted">{{ $machine->name }}</div>
        </div>

        <a href="{{ url('/monitoring/' . $machine->code . '/technology') }}"
           class="btn btn-outline-primary">
            Mở màn hình công nghệ
        </a>
    </div>

    <div id="tag-alert"></div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-lg-4">
                    <input
                        type="text"
                        name="keyword"
                        value="{{ request('keyword') }}"
                        class="form-control"
                        placeholder="Tên tag, địa chỉ, source key..."
                    >
                </div>

                <div class="col-lg-2">
                    <select name="data_type" class="form-select">
                        <option value="">Tất cả kiểu dữ liệu</option>
                        @foreach($dataTypes as $dataType)
                            <option
                                value="{{ $dataType }}"
                                @selected(request('data_type') === $dataType)
                            >
                                {{ $dataType }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <select name="display_group" class="form-select">
                        <option value="">Tất cả nhóm</option>
                        @foreach($groups as $group)
                            <option
                                value="{{ $group }}"
                                @selected(request('display_group') === $group)
                            >
                                {{ $group }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <select name="visibility" class="form-select">
                        <option value="">Ẩn và hiện</option>
                        <option value="visible" @selected(request('visibility') === 'visible')>
                            Đang hiển thị
                        </option>
                        <option value="hidden" @selected(request('visibility') === 'hidden')>
                            Đang ẩn
                        </option>
                    </select>
                </div>

                <div class="col-lg-2 d-grid">
                    <button class="btn btn-primary">Tìm kiếm</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">Cập nhật hàng loạt</div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label">Nhóm hiển thị</label>
                    <input id="bulk-display-group" class="form-control">
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Hiển thị</label>
                    <select id="bulk-is-visible" class="form-select">
                        <option value="">Không thay đổi</option>
                        <option value="1">Hiện</option>
                        <option value="0">Ẩn</option>
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Lưu lịch sử</label>
                    <select id="bulk-history-enabled" class="form-select">
                        <option value="">Không thay đổi</option>
                        <option value="1">Bật</option>
                        <option value="0">Tắt</option>
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Deadband</label>
                    <input
                        id="bulk-history-deadband"
                        type="number"
                        step="0.000001"
                        min="0"
                        class="form-control"
                    >
                </div>

                <div class="col-lg-3 d-grid">
                    <button id="btn-bulk-update" class="btn btn-outline-primary">
                        Cập nhật tag đã chọn
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px">
                            <input id="check-all" type="checkbox" class="form-check-input">
                        </th>
                        <th>Nguồn PLC</th>
                        <th>Tên hiển thị</th>
                        <th>Nhóm</th>
                        <th>Giá trị hiện tại</th>
                        <th>Deadband</th>
                        <th>Hiển thị</th>
                        <th>Lịch sử</th>
                        <th style="width: 90px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tags as $tag)
                        <tr id="tag-row-{{ $tag->id }}">
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-check-input tag-checkbox"
                                    value="{{ $tag->id }}"
                                >
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $tag->source_name }}</div>
                                <div class="small text-muted">
                                    {{ $tag->address ?: 'Không có địa chỉ' }}
                                    · {{ $tag->data_type }}
                                </div>
                            </td>

                            <td>
                                <div class="tag-display-name fw-semibold">
                                    {{ $tag->display_name ?: $tag->source_name }}
                                </div>
                                <div class="small text-muted tag-unit">
                                    {{ $tag->unit ?: 'Chưa có đơn vị' }}
                                </div>
                            </td>

                            <td class="tag-display-group">
                                {{ $tag->display_group ?: 'Chưa phân nhóm' }}
                            </td>

                            <td>
                                @php
                                    $currentValue = $tag->currentValue?->value
                                        ?? $tag->currentValue?->value_text
                                        ?? $tag->currentValue?->value_string
                                        ?? $tag->currentValue?->raw_value;
                                @endphp

                                <span class="fw-semibold">
                                    {{ $currentValue ?? '—' }}
                                </span>
                                <div class="small text-muted">
                                    {{ optional($tag->currentValue?->collected_at)->format('d/m/Y H:i:s') }}
                                </div>
                            </td>

                            <td class="tag-history-deadband">
                                {{ $tag->history_deadband }}
                            </td>

                            <td class="tag-is-visible">
                                @if($tag->is_visible)
                                    <span class="badge text-bg-success">Hiện</span>
                                @else
                                    <span class="badge text-bg-secondary">Ẩn</span>
                                @endif
                            </td>

                            <td class="tag-history-enabled">
                                @if($tag->history_enabled)
                                    <span class="badge text-bg-primary">Bật</span>
                                @else
                                    <span class="badge text-bg-secondary">Tắt</span>
                                @endif
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary btn-edit-tag"
                                    data-tag-id="{{ $tag->id }}"
                                >
                                    Sửa
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                Không tìm thấy tag.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tags->hasPages())
            <div class="card-footer">
                {{ $tags->links() }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="tag-edit-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="tag-edit-form" class="modal-content">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">Cấu hình tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="edit-tag-id">

                <div class="alert alert-light border">
                    <div><strong id="edit-source-name"></strong></div>
                    <div class="small text-muted">
                        <span id="edit-address"></span>
                        · <span id="edit-data-type"></span>
                    </div>
                </div>

                <div id="edit-errors"></div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Tên hiển thị</label>
                        <input
                            name="display_name"
                            id="edit-display-name"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Đơn vị</label>
                        <input name="unit" id="edit-unit" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nhóm hiển thị</label>
                        <input
                            name="display_group"
                            id="edit-display-group"
                            class="form-control"
                            list="display-group-list"
                        >
                        <datalist id="display-group-list">
                            @foreach($groups as $group)
                                <option value="{{ $group }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Thứ tự</label>
                        <input
                            name="display_order"
                            id="edit-display-order"
                            type="number"
                            min="0"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Số lẻ</label>
                        <input
                            name="decimal_places"
                            id="edit-decimal-places"
                            type="number"
                            min="0"
                            max="6"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Deadband</label>
                        <input
                            name="history_deadband"
                            id="edit-history-deadband"
                            type="number"
                            min="0"
                            step="0.000001"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cảnh báo thấp</label>
                        <input
                            name="alarm_low"
                            id="edit-alarm-low"
                            type="number"
                            step="0.000001"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cảnh báo cao</label>
                        <input
                            name="alarm_high"
                            id="edit-alarm-high"
                            type="number"
                            step="0.000001"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input
                                name="is_visible"
                                id="edit-is-visible"
                                value="1"
                                type="checkbox"
                                class="form-check-input"
                            >
                            <label class="form-check-label" for="edit-is-visible">
                                Hiển thị trên màn hình giám sát
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input
                                name="history_enabled"
                                id="edit-history-enabled"
                                value="1"
                                type="checkbox"
                                class="form-check-input"
                            >
                            <label class="form-check-label" for="edit-history-enabled">
                                Lưu dữ liệu lịch sử
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Đóng
                </button>
                <button id="btn-save-tag" class="btn btn-primary">
                    Lưu cấu hình
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.machineTagConfig = {
    machineCode: @json($machine->code),
    editUrlTemplate: @json(url('/machines/' . $machine->code . '/tags/__TAG__')),
    bulkUrl: @json(url('/machines/' . $machine->code . '/tags/bulk-update')),
    csrfToken: @json(csrf_token()),
};
</script>
<script src="{{ asset('js/machine-tags.js') }}"></script>
@endpush
