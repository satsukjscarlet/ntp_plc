@extends('layouts.app')

@section('title', 'Thông số công nghệ ' . $machine->code)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/m62-technology.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3 m62-monitoring"
     data-machine-code="{{ $machine->code }}">

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="row align-items-end g-3">
                <div class="col-lg-3 col-md-5">
                    <label for="view-mode" class="form-label fw-semibold mb-1">
                        Chế độ xem
                    </label>
                    <select id="view-mode" class="form-select form-select-sm">
                        <option value="realtime">Realtime</option>
                    </select>
                </div>

                <div class="col-lg-6 col-md-4 text-md-center">
                    <button type="button"
                            id="realtime-button"
                            class="btn btn-success btn-sm px-4">
                        <span class="realtime-dot"></span>
                        Realtime
                    </button>
                    <div class="small text-muted mt-1">
                        Tự động cập nhật sau <span id="countdown">5</span>s
                    </div>
                </div>

                <div class="col-lg-3 col-md-3 text-md-end">
                    <a href="{{ url('/machines/' . $machine->code . '/tags') }}"
                       class="btn btn-outline-secondary btn-sm">
                        Quản lý tag
                    </a>
                </div>
            </div>

            <div id="realtime-message"
                 class="alert alert-info py-2 px-3 mt-3 mb-0">
                <strong>Dữ liệu realtime</strong>
                <span id="last-update-text">- Đang tải dữ liệu...</span>
            </div>
        </div>
    </div>

    <div id="monitoring-error"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <h5 class="text-center fw-normal mb-3">
                Tham số công nghệ AMUT
            </h5>

            {{-- ZONE NHIỆT 1 --}}
            <div class="temperature-table-wrapper mb-3">
                <table class="table table-bordered temperature-table mb-0">
                    <tbody>
                        <tr>
                            <th class="row-title">Zone nhiệt</th>
                            @foreach (['CL','XL1','XL2','XL3','XL4','XL5','CN','XLMC1','XLMC2'] as $zone)
                                <th class="zone-title" data-zone-header="{{ $zone }}">{{ $zone }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row-title">Nhiệt độ đặt</th>
                            @foreach (['CL','XL1','XL2','XL3','XL4','XL5','CN','XLMC1','XLMC2'] as $zone)
                                <td><div class="value-box" data-zone="{{ $zone }}" data-kind="set">—</div></td>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row-title">Nhiệt độ thực tế</th>
                            @foreach (['CL','XL1','XL2','XL3','XL4','XL5','CN','XLMC1','XLMC2'] as $zone)
                                <td><div class="value-box" data-zone="{{ $zone }}" data-kind="actual">—</div></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- ZONE NHIỆT 2 --}}
            <div class="temperature-table-wrapper mb-3">
                <table class="table table-bordered temperature-table mb-0">
                    <tbody>
                        <tr>
                            <th class="row-title">Zone nhiệt</th>
                            @foreach (['XLMC3','XLMC4','DH1','DH2','DH3','DH4','DH5','DH6'] as $zone)
                                <th class="zone-title" data-zone-header="{{ $zone }}">{{ $zone }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row-title">Nhiệt độ đặt</th>
                            @foreach (['XLMC3','XLMC4','DH1','DH2','DH3','DH4','DH5','DH6'] as $zone)
                                <td><div class="value-box" data-zone="{{ $zone }}" data-kind="set">—</div></td>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="row-title">Nhiệt độ thực tế</th>
                            @foreach (['XLMC3','XLMC4','DH1','DH2','DH3','DH4','DH5','DH6'] as $zone)
                                <td><div class="value-box" data-zone="{{ $zone }}" data-kind="actual">—</div></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- THÔNG SỐ CÔNG NGHỆ CHÍNH --}}
            <div class="section-title">Thông số vận hành</div>
            <div class="parameter-grid mb-3">
                <div class="parameter-column">
                    @foreach ([
                        ['key' => 'screw_speed', 'label' => 'Tốc độ VX'],
                        ['key' => 'screw_load', 'label' => 'Tải VX'],
                        ['key' => 'extruder_balance', 'label' => 'Cân thực tế'],
                        ['key' => 'melt_pressure', 'label' => 'Áp lực nhựa'],
                        ['key' => 'specific_consumption', 'label' => 'Suất tiêu hao'],
                    ] as $item)
                        <div class="parameter-row">
                            <div class="parameter-label">{{ $item['label'] }}</div>
                            <div class="parameter-value" data-metric="{{ $item['key'] }}">—</div>
                        </div>
                    @endforeach
                </div>

                <div class="parameter-column">
                    @foreach ([
                        ['key' => 'thread_speed', 'label' => 'Tốc độ máy chỉ'],
                        ['key' => 'thread_load', 'label' => 'Tải máy chỉ'],
                        ['key' => 'puller_speed', 'label' => 'Tốc độ dàn kéo'],
                        ['key' => 'weight_per_meter', 'label' => 'Định lượng g/m TT'],
                    ] as $item)
                        <div class="parameter-row">
                            <div class="parameter-label">{{ $item['label'] }}</div>
                            <div class="parameter-value" data-metric="{{ $item['key'] }}">—</div>
                        </div>
                    @endforeach
                </div>

                <div class="parameter-column">
                    @foreach ([
                        ['key' => 'ck1_temperature', 'label' => 'Nhiệt độ CK1'],
                        ['key' => 'set_weight', 'label' => 'Định lượng đặt'],
                        ['key' => 'ck1_pressure', 'label' => 'Áp suất bể CK1'],
                        ['key' => 'set_balance', 'label' => 'Cài đặt cân định lượng'],
                    ] as $item)
                        <div class="parameter-row">
                            <div class="parameter-label">{{ $item['label'] }}</div>
                            <div class="parameter-value" data-metric="{{ $item['key'] }}">—</div>
                        </div>
                    @endforeach
                </div>

                <div class="parameter-column">
                    @foreach ([
                        ['key' => 'productivity', 'label' => 'Năng suất (kg/h)'],
                        ['key' => 'melt_temperature', 'label' => 'Nhiệt độ nhựa'],
                        ['key' => 'vacuum', 'label' => 'Vacuum'],
                        ['key' => 'line_speed', 'label' => 'Tốc độ dây chuyền'],
                    ] as $item)
                        <div class="parameter-row">
                            <div class="parameter-label">{{ $item['label'] }}</div>
                            <div class="parameter-value" data-metric="{{ $item['key'] }}">—</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- THÔNG TIN SẢN XUẤT --}}
            <div class="section-title">Thông tin sản xuất</div>
            <div class="production-grid mb-3">
                @foreach ([
                    ['key' => 'production_date', 'label' => 'Ngày'],
                    ['key' => 'shift', 'label' => 'Ca'],
                    ['key' => 'team', 'label' => 'Tổ'],
                    ['key' => 'product_code', 'label' => 'Sản phẩm'],
                    ['key' => 'material_type', 'label' => 'Loại NL'],
                    ['key' => 'diameter', 'label' => 'Đường kính'],
                    ['key' => 'puller_setting', 'label' => 'Chuốt'],
                    ['key' => 'material_1', 'label' => 'Nguyên liệu 1'],
                    ['key' => 'material_2', 'label' => 'Nguyên liệu 2'],
                    ['key' => 'stop_hours', 'label' => 'Giờ dừng'],
                    ['key' => 'heating_hours', 'label' => 'Giờ gia nhiệt'],
                    ['key' => 'running_hours', 'label' => 'Giờ chạy'],
                ] as $item)
                    <div class="production-item">
                        <div class="production-label">{{ $item['label'] }}</div>
                        <div class="production-value" data-production="{{ $item['key'] }}">—</div>
                    </div>
                @endforeach
            </div>

            {{-- TRẠNG THÁI HỆ THỐNG --}}
            <div class="section-title">Trạng thái hệ thống</div>
            <div class="system-grid mb-3">
                <div class="system-item">
                    <span>Máy</span>
                    <strong id="machine-status-value">—</strong>
                </div>
                <div class="system-item">
                    <span>Dữ liệu gần nhất</span>
                    <strong id="machine-last-seen">—</strong>
                </div>
                <div class="system-item">
                    <span>Tổng tag hiển thị</span>
                    <strong id="machine-total-tags">—</strong>
                </div>
                <div class="system-item">
                    <span>Chu kỳ cập nhật</span>
                    <strong>5 giây</strong>
                </div>
            </div>

            {{-- HIỂN THỊ TỰ ĐỘNG CÁC TAG CÒN LẠI --}}
            <div class="section-title d-flex justify-content-between align-items-center">
                <span>Thông số bổ sung</span>
                <span class="badge text-bg-secondary" id="extra-tag-count">0 tag</span>
            </div>
            <div id="extra-tag-groups">
                <div class="text-muted py-3">Đang tải dữ liệu...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.m62TechnologyConfig = {
    machineCode: @json($machine->code),
    apiUrl: @json(url('/api/monitoring/' . $machine->code)),
    refreshInterval: 5000
};
</script>
<script src="{{ asset('js/m62-technology.js') }}"></script>
@endpush
