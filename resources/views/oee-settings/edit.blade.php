@extends('layouts.app')

@section('title', 'Cấu hình OEE ' . $machine->code)
@section('page_title', 'Cấu hình OEE máy ' . $machine->code)
@section('page_subtitle', 'A lấy MD488 trong ca kế tiếp; P lấy MD264 trong chính ca')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('oee-settings.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Danh sách cấu hình
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('machines.oee-settings.update', $machine) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <strong>Công thức:</strong>
                        A = MD488 của ca kế tiếp / tổng thời gian ca;
                        P = MD264 của chính ca / năng suất định mức kg/h;
                        Q = Chính phẩm / Tổng sản lượng.
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h5 class="mb-3">Availability – MD488</h5>

                        <label class="form-label">Tag “Giờ chạy ca trước”</label>
                        <select name="availability_tag_id" class="form-select" required>
                            <option value="">-- Chọn tag MD488 --</option>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    @selected(old('availability_tag_id', $setting->availability_tag_id) == $tag->id)>
                                    [ID {{ $tag->id }}]
                                    {{ $tag->display_name ?: $tag->source_name }}
                                    — {{ $tag->address ?: $tag->source_key }}
                                </option>
                            @endforeach
                        </select>

                        <label class="form-label mt-3">Đơn vị giá trị MD488</label>
                        <select name="availability_value_unit" class="form-select" required>
                            @foreach([
                                'hours' => 'Giờ',
                                'minutes' => 'Phút',
                                'seconds' => 'Giây',
                            ] as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('availability_value_unit', $setting->availability_value_unit ?: 'hours') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @if($availabilityStats)
                            <div class="row g-2 text-center mt-3">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <small>Min</small>
                                        <div class="fw-bold">{{ number_format((float) $availabilityStats['min'], 4) }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <small>Max</small>
                                        <div class="fw-bold">{{ number_format((float) $availabilityStats['max'], 4) }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <small>TB</small>
                                        <div class="fw-bold">{{ number_format((float) $availabilityStats['avg'], 4) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">
                                {{ number_format($availabilityStats['count']) }} mẫu hợp lệ trong 24 giờ.
                            </div>
                        @else
                            <div class="alert alert-warning small mt-3 mb-0">
                                Chưa có dữ liệu số hợp lệ 24 giờ gần nhất cho MD488.
                                Hệ thống vẫn lưu cấu hình và sẽ báo thiếu dữ liệu khi tính OEE.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h5 class="mb-3">Performance – MD264</h5>

                        <label class="form-label">Tag “Năng suất máy”</label>
                        <select name="performance_tag_id" class="form-select" required>
                            <option value="">-- Chọn tag MD264 --</option>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    @selected(old('performance_tag_id', $setting->performance_tag_id) == $tag->id)>
                                    [ID {{ $tag->id }}]
                                    {{ $tag->display_name ?: $tag->source_name }}
                                    — {{ $tag->address ?: $tag->source_key }}
                                </option>
                            @endforeach
                        </select>

                        <label class="form-label mt-3">Cách lấy MD264 trong ca</label>
                        <select name="performance_aggregation" class="form-select" required>
                            @foreach([
                                'stable_average' => 'Trung bình các khoảng ổn định (khuyến nghị)',
                                'average' => 'Trung bình toàn bộ mẫu dương',
                                'last' => 'Mẫu cuối ca',
                                'maximum' => 'Giá trị lớn nhất trong ca',
                            ] as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('performance_aggregation', $setting->performance_aggregation ?: 'stable_average') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Biên độ ổn định ± (kg/h)</label>
                                <input type="number"
                                       name="performance_stability_tolerance"
                                       step="0.01"
                                       min="0.01"
                                       class="form-control"
                                       value="{{ old('performance_stability_tolerance', $setting->performance_stability_tolerance ?? 1) }}"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Thời gian ổn định tối thiểu (giây)</label>
                                <input type="number"
                                       name="performance_stability_min_seconds"
                                       min="5"
                                       max="3600"
                                       class="form-control"
                                       value="{{ old('performance_stability_min_seconds', $setting->performance_stability_min_seconds ?? 60) }}"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Giá trị hợp lệ tối thiểu (kg/h)</label>
                                <input type="number"
                                       name="performance_min_valid_value"
                                       step="0.01"
                                       min="0"
                                       class="form-control"
                                       value="{{ old('performance_min_valid_value', $setting->performance_min_valid_value ?? 0) }}"
                                       required>
                            </div>
                        </div>

                        <div class="small text-muted mt-2">
                            Mặc định: chỉ lấy các khoảng MD264 dương, dao động giữa hai mẫu liên tiếp không quá ±1 kg/h
                            và duy trì liên tục ít nhất 60 giây.
                        </div>

                        @if($performanceStats)
                            <div class="row g-2 text-center mt-3">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <small>Min</small>
                                        <div class="fw-bold">{{ number_format((float) $performanceStats['min'], 4) }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <small>Max</small>
                                        <div class="fw-bold">{{ number_format((float) $performanceStats['max'], 4) }}</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <small>TB</small>
                                        <div class="fw-bold">{{ number_format((float) $performanceStats['avg'], 4) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">
                                {{ number_format($performanceStats['count']) }} mẫu hợp lệ trong 24 giờ.
                            </div>
                        @else
                            <div class="alert alert-warning small mt-3 mb-0">
                                Chưa có dữ liệu số hợp lệ 24 giờ gần nhất cho MD264.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input"
                               type="checkbox"
                               name="is_active"
                               value="1"
                               id="is_active"
                               @checked(old('is_active', $setting->exists ? $setting->is_active : true))>
                        <label class="form-check-label" for="is_active">
                            Kích hoạt cấu hình OEE
                        </label>
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-4">
                <button class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Lưu cấu hình
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
