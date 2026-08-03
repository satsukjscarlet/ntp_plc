@extends('layouts.app')

@section('title', 'Cấu hình vị trí hiển thị tag')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/monitoring-layout-editor.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3 monitoring-layout-editor">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Cấu hình vị trí hiển thị tag</h4>
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
        <strong>Hướng dẫn:</strong>
        chọn một tag ở cột bên trái, sau đó chọn loại thông số, dòng và cột ở
        khung bên phải. Với thông số nhiệt, cần chọn đủ
        <strong>dòng nhiệt độ</strong> và <strong>cột zone</strong>.
    </div>

    <div class="editor-grid">
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="fw-semibold mb-2">1. Chọn tag PLC</div>

                <form method="GET">
                    <div class="input-group input-group-sm">
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               class="form-control"
                               placeholder="Tìm tên tag, địa chỉ hoặc mã nguồn">
                        <button class="btn btn-primary">Tìm kiếm</button>
                    </div>
                </form>
            </div>

            <div class="tag-list">
                @forelse ($tags as $tag)
                    <button type="button"
                            class="tag-list-item js-select-tag"
                            data-tag-id="{{ $tag->id }}">
                        <span class="tag-list-name">
                            {{ $tag->display_name ?: $tag->source_name }}
                        </span>
                        <span class="tag-list-address">
                            {{ $tag->address ?: $tag->source_key }}
                        </span>
                    </button>
                @empty
                    <div class="text-muted p-3">
                        Không tìm thấy tag phù hợp.
                    </div>
                @endforelse
            </div>

            @if ($tags->hasPages())
                <div class="card-footer">
                    {{ $tags->links() }}
                </div>
            @endif
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">
                2. Chọn vị trí hiển thị
            </div>

            <div class="card-body">
                <div id="empty-selection" class="empty-selection">
                    Hãy chọn một tag ở danh sách bên trái.
                </div>

                <div id="tag-editor" class="d-none">
                    <div class="selected-tag-box mb-3">
                        <div class="small text-muted">Tag đang cấu hình</div>
                        <div id="selected-tag-name" class="fw-semibold"></div>
                        <div id="selected-tag-address" class="small"></div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="show-on-monitoring">
                        <label class="form-check-label fw-semibold"
                               for="show-on-monitoring">
                            Hiển thị tag này trên màn hình giám sát
                        </label>
                    </div>

                    <div id="position-fields">
                        <div class="mb-3">
                            <label for="section-select" class="form-label">
                                Loại thông số
                            </label>
                            <select id="section-select" class="form-select">
                                <option value="">-- Chọn loại thông số --</option>
                            </select>
                            <div class="form-text">
                                Ví dụ: Thông số nhiệt, Thông số vận hành,
                                Trạng thái hệ thống, Thông tin sản xuất.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="row-select" class="form-label">
                                Dòng hiển thị
                            </label>
                            <select id="row-select" class="form-select">
                                <option value="">-- Chọn dòng --</option>
                            </select>
                            <div class="form-text">
                                Ví dụ: Nhiệt độ đặt, Nhiệt độ thực tế, Hàng 1...
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="column-select" class="form-label">
                                Cột hiển thị
                            </label>
                            <select id="column-select" class="form-select">
                                <option value="">-- Chọn cột --</option>
                            </select>
                            <div class="form-text">
                                Ví dụ: CL, XL1, DH10, DH11 hoặc Cột 1...
                            </div>
                        </div>

                        <div class="preview-box mb-3">
                            <div class="small text-muted mb-1">
                                Vị trí sau khi lưu
                            </div>
                            <div id="position-preview" class="fw-semibold">
                                Chưa chọn vị trí
                            </div>
                        </div>
                    </div>

                    <button type="button"
                            id="save-position"
                            class="btn btn-success">
                        Lưu vị trí hiển thị
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header fw-semibold">
            3. Thêm dòng hoặc cột mới
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <form id="add-row-form" class="border rounded p-3">
                        <h6>Thêm dòng hiển thị</h6>

                        <div class="mb-2">
                            <label class="form-label">Loại thông số</label>
                            <select name="section_id"
                                    class="form-select"
                                    required>
                                <option value="">-- Chọn loại thông số --</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}">
                                        {{ $section->name_vi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Mã dòng</label>
                                <input name="code"
                                       class="form-control"
                                       placeholder="vd: alarm_high"
                                       required>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Tên tiếng Việt</label>
                                <input name="name_vi"
                                       class="form-control"
                                       placeholder="vd: Nhiệt độ cảnh báo cao"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Thứ tự</label>
                                <input name="display_order"
                                       type="number"
                                       min="0"
                                       value="0"
                                       class="form-control">
                            </div>
                        </div>

                        <button class="btn btn-primary mt-2">
                            Thêm dòng
                        </button>
                    </form>
                </div>

                <div class="col-lg-6">
                    <form id="add-column-form" class="border rounded p-3">
                        <h6>Thêm cột hiển thị</h6>

                        <div class="mb-2">
                            <label class="form-label">Loại thông số</label>
                            <select name="section_id"
                                    class="form-select"
                                    required>
                                <option value="">-- Chọn loại thông số --</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}">
                                        {{ $section->name_vi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Mã cột</label>
                                <input name="code"
                                       class="form-control"
                                       placeholder="vd: dh21"
                                       required>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Tên tiếng Việt</label>
                                <input name="name_vi"
                                       class="form-control"
                                       placeholder="vd: DH21"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Thứ tự</label>
                                <input name="display_order"
                                       type="number"
                                       min="0"
                                       value="0"
                                       class="form-control">
                            </div>
                        </div>

                        <button class="btn btn-primary mt-2">
                            Thêm cột
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.monitoringLayoutEditorConfig = {
    csrfToken: @json(csrf_token()),
    machineCode: @json($machine->code),
    updateUrlTemplate: @json(url('/machines/' . $machine->code . '/monitoring-layout/tags/__TAG_ID__')),
    addRowUrl: @json(url('/machines/' . $machine->code . '/monitoring-layout/rows')),
    addColumnUrl: @json(url('/machines/' . $machine->code . '/monitoring-layout/columns')),
    sections: {!! $layoutOptionsJson !!},
    tags: {!! $tagItemsJson !!}
};
</script>
<script src="{{ asset('js/monitoring-layout-editor.js') }}"></script>
@endpush
