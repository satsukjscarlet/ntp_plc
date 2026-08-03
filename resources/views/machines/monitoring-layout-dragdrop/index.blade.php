@extends('layouts.app')

@section('title', 'Bố trí tag bằng kéo thả')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/monitoring-layout-dragdrop.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3 monitoring-layout-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Bố trí tag trên màn hình giám sát</h4>
            <div class="text-muted">
                Máy {{ $machine->code }} - {{ $machine->name }}
            </div>
        </div>

        <a class="btn btn-outline-primary"
           href="{{ url('/monitoring/' . $machine->code . '/technology') }}">
            Xem màn hình công nghệ
        </a>
    </div>

    <div id="page-message"></div>

    <div class="alert alert-info">
        <strong>Cách sử dụng:</strong>
        kéo tag từ danh sách bên trái và thả vào đúng ô ở bên phải.
        Muốn bỏ bố trí, kéo tag về ô
        <strong>Tag chưa bố trí</strong> hoặc bấm dấu × trên tag.
        Bấm nút bánh răng trên tag để cấu hình tên, đơn vị và cảnh báo.
    </div>

    <div class="layout-workspace">
        <aside class="tag-sidebar card shadow-sm">
            <div class="card-header">
                <div class="fw-semibold mb-2">Tag chưa bố trí</div>

                <input type="search"
                       id="tag-search"
                       class="form-control form-control-sm"
                       placeholder="Tìm theo tên hoặc địa chỉ tag">
            </div>

            <div id="unassigned-dropzone"
                 class="unassigned-dropzone"
                 data-unassigned-zone="1">
                <div class="dropzone-hint">
                    Thả tag vào đây để bỏ vị trí hiển thị
                </div>

                <div id="unassigned-tag-list" class="tag-card-list"></div>
            </div>
        </aside>

        <main class="layout-main card shadow-sm">
            <div class="card-header p-0">
                <ul class="nav nav-tabs px-3 pt-2" role="tablist">
                    @foreach ($sections as $index => $section)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link @if ($index === 0) active @endif"
                                    data-bs-toggle="tab"
                                    data-bs-target="#section-{{ $section->id }}"
                                    type="button"
                                    role="tab">
                                {{ $section->name_vi }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    @foreach ($sections as $index => $section)
                        <div class="tab-pane fade @if ($index === 0) show active @endif"
                             id="section-{{ $section->id }}"
                             role="tabpanel">
                            <div class="section-title">
                                {{ $section->name_vi }}
                            </div>

                            <div class="layout-section-grid">
                                @foreach ($section->rows as $row)
                                    <div class="layout-row-group">
                                        <div class="layout-row-group-title">
                                            {{ $row->name_vi }}
                                        </div>

                                        <div class="layout-cell-grid">
                                            @foreach ($section->columns as $column)
                                                <div class="layout-cell"
                                                     data-section-id="{{ $section->id }}"
                                                     data-row-id="{{ $row->id }}"
                                                     data-column-id="{{ $column->id }}">
                                                    <div class="layout-cell-column-name"
                                                         title="{{ $column->name_vi }}">
                                                        {{ $column->name_vi }}
                                                    </div>

                                                    <div class="cell-placeholder">
                                                        Kéo tag vào đây
                                                    </div>

                                                    <div class="cell-tag-container"></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($section->rows->isEmpty() || $section->columns->isEmpty())
                                <div class="alert alert-warning mb-0">
                                    Loại thông số này chưa có đủ dòng hoặc cột.
                                    Hãy tạo dòng và cột trước khi kéo thả tag.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade"
     id="tag-settings-modal"
     tabindex="-1"
     aria-labelledby="tag-settings-modal-label"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="tag-settings-form">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="tag-settings-modal-label">
                            Cấu hình hiển thị tag
                        </h5>
                        <div class="small text-muted" id="settings-tag-source"></div>
                    </div>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="settings-tag-id">

                    <div id="settings-errors"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="settings-display-name">
                                Tên hiển thị
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="settings-display-name"
                                   maxlength="255">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="settings-display-unit">
                                Đơn vị
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="settings-display-unit"
                                   maxlength="30"
                                   placeholder="°C, bar, kg/h...">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="settings-display-decimals">
                                Số chữ số thập phân
                            </label>
                            <input type="number"
                                   class="form-control"
                                   id="settings-display-decimals"
                                   min="0"
                                   max="6"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="settings-warning-low">
                                Ngưỡng thấp
                            </label>
                            <input type="number"
                                   step="any"
                                   class="form-control"
                                   id="settings-warning-low">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="settings-warning-high">
                                Ngưỡng cao
                            </label>
                            <input type="number"
                                   step="any"
                                   class="form-control"
                                   id="settings-warning-high">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="settings-display-color">
                                Màu viền mặc định
                            </label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="settings-color-picker"
                                       value="#1f4e78"
                                       title="Chọn màu">
                                <input type="text"
                                       class="form-control"
                                       id="settings-display-color"
                                       maxlength="30"
                                       placeholder="#1f4e78">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="settings-preview">
                                <div class="small text-muted mb-1">Xem trước</div>
                                <div id="settings-preview-card" class="settings-preview-card">
                                    <div id="settings-preview-name" class="fw-semibold">
                                        Tên tag
                                    </div>
                                    <div>
                                        <span id="settings-preview-value" class="settings-preview-value">
                                            215.3
                                        </span>
                                        <span id="settings-preview-unit"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="settings-alarm-enabled">
                                        <label class="form-check-label"
                                               for="settings-alarm-enabled">
                                            Bật cảnh báo ngưỡng
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="settings-blink-on-alarm">
                                        <label class="form-check-label"
                                               for="settings-blink-on-alarm">
                                            Nhấp nháy khi cảnh báo
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="settings-chart-enabled">
                                        <label class="form-check-label"
                                               for="settings-chart-enabled">
                                            Cho phép xem biểu đồ
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Hủy
                    </button>
                    <button type="submit"
                            class="btn btn-primary"
                            id="settings-save-button">
                        Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.monitoringLayoutDragDropConfig = {
    csrfToken: @json(csrf_token()),
    updateUrlTemplate: @json(
        url('/machines/' . $machine->code . '/monitoring-layout/tags/__TAG_ID__')
    ),
    sections: {!! $layoutDataJson !!},
    tags: {!! $allTagsJson !!}
};
</script>
<script src="{{ asset('js/monitoring-layout-dragdrop.js') }}"></script>
@endpush
