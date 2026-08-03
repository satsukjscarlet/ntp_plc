@extends('layouts.app')

@section('title', 'Cấu hình vị trí hiển thị tag - ' . $machine->code)

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Cấu hình vị trí hiển thị tag</h4>
            <div class="text-muted">
                Máy {{ $machine->code }} - {{ $machine->name }}
            </div>
        </div>

        <a href="{{ url('/monitoring/' . $machine->code . '/technology') }}"
           class="btn btn-outline-primary">
            Xem màn hình thông số công nghệ
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div id="page-message"></div>

    <div class="alert alert-info">
        <strong>Cách sử dụng:</strong>
        chọn loại thông số, chọn dòng, chọn cột và bấm
        <strong>Lưu vị trí</strong>. Với thông số nhiệt, bắt buộc chọn cả
        dòng nhiệt độ và cột zone như CL, XL1, DH10, DH11...
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold">Thêm nhanh dòng hoặc cột mới</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-6">
                    <form method="POST"
                          action="{{ route('monitoring-layout.rows.store', $machine->code) }}"
                          class="border rounded p-3">
                        @csrf
                        <h6>Thêm dòng hiển thị</h6>

                        <div class="mb-2">
                            <label class="form-label">Loại thông số</label>
                            <select name="section_id" class="form-select" required>
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
                                <label class="form-label">Tên dòng tiếng Việt</label>
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
                    <form method="POST"
                          action="{{ route('monitoring-layout.columns.store', $machine->code) }}"
                          class="border rounded p-3">
                        @csrf
                        <h6>Thêm cột hiển thị</h6>

                        <div class="mb-2">
                            <label class="form-label">Loại thông số</label>
                            <select name="section_id" class="form-select" required>
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
                                <label class="form-label">Tên cột tiếng Việt</label>
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

    <div class="card">
        <div class="card-header fw-semibold">
            Danh sách tag và vị trí hiển thị
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 260px">Tag PLC</th>
                        <th style="min-width: 200px">Loại thông số</th>
                        <th style="min-width: 190px">Dòng hiển thị</th>
                        <th style="min-width: 160px">Cột hiển thị</th>
                        <th style="width: 130px"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tags as $tag)
                    <tr data-tag-row="{{ $tag->id }}">
                        <td>
                            <div class="fw-semibold">
                                {{ $tag->display_name ?: $tag->source_name }}
                            </div>
                            <div class="small text-muted">
                                {{ $tag->address ?: $tag->source_key }}
                            </div>
                        </td>

                        <td>
                            <select class="form-select form-select-sm js-section"
                                    data-current="{{ $tag->monitoring_section_id }}">
                                <option value="">Không đưa lên màn hình</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}"
                                            @selected($tag->monitoring_section_id == $section->id)>
                                        {{ $section->name_vi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <select class="form-select form-select-sm js-row"
                                    data-current="{{ $tag->monitoring_row_id }}">
                                <option value="">Chưa chọn dòng</option>
                            </select>
                        </td>

                        <td>
                            <select class="form-select form-select-sm js-column"
                                    data-current="{{ $tag->monitoring_column_id }}">
                                <option value="">Chưa chọn cột</option>
                            </select>
                        </td>

                        <td>
                            <button type="button"
                                    class="btn btn-success btn-sm w-100 js-save-position"
                                    data-url="{{ route('monitoring-layout.tags.update', [$machine->code, $tag]) }}">
                                Lưu vị trí
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Chưa có tag.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($tags->hasPages())
            <div class="card-footer">
                {{ $tags->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
window.monitoringLayoutConfig = {
    csrfToken: @json(csrf_token()),
    sections: @json(
        $sections->map(fn ($section) => [
            'id' => $section->id,
            'code' => $section->code,
            'name' => $section->name_vi,
            'rows' => $section->rows->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name_vi,
            ])->values(),
            'columns' => $section->columns->map(fn ($column) => [
                'id' => $column->id,
                'name' => $column->name_vi,
            ])->values(),
        ])->values()
    )
};
</script>
<script src="{{ asset('js/monitoring-layout.js') }}"></script>
@endpush
