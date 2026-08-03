@extends('layouts.app')

@section('title', 'Giám sát công nghệ ' . $machine->code)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/monitoring-runtime.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3 monitoring-runtime">
    <div class="runtime-header mb-3">
        <div>
            <h3 class="mb-1">
                Giám sát công nghệ {{ $machine->code }}
            </h3>
            <div class="text-muted">
                {{ $machine->name }}
            </div>
        </div>

        <div class="runtime-status-box">
            <div>
                <span class="runtime-status-dot" id="runtime-status-dot"></span>
                <span id="runtime-connection-text">Đang kết nối...</span>
            </div>
            <div class="small text-muted">
                Cập nhật:
                <span id="runtime-last-update">--</span>
            </div>
        </div>
    </div>

    <div class="runtime-summary mb-3" aria-label="Tổng hợp trạng thái">
        <div class="runtime-summary-item">
            <span>Tổng tag</span>
            <strong id="runtime-summary-total">0</strong>
        </div>
        <div class="runtime-summary-item normal">
            <span>Bình thường</span>
            <strong id="runtime-summary-normal">0</strong>
        </div>
        <div class="runtime-summary-item alarm">
            <span>Cảnh báo</span>
            <strong id="runtime-summary-alarm">0</strong>
        </div>
        <div class="runtime-summary-item high">
            <span>Ngưỡng cao</span>
            <strong id="runtime-summary-high">0</strong>
        </div>
        <div class="runtime-summary-item low">
            <span>Ngưỡng thấp</span>
            <strong id="runtime-summary-low">0</strong>
        </div>
        <div class="runtime-summary-item stale">
            <span>Dữ liệu chậm</span>
            <strong id="runtime-summary-stale">0</strong>
        </div>
    </div>

    <div id="runtime-message"></div>

    <div id="runtime-sections"></div>
</div>

<div class="modal fade"
     id="runtime-trend-modal"
     tabindex="-1"
     aria-labelledby="runtime-trend-title"
     aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="runtime-trend-title">
                        Biểu đồ lịch sử
                    </h5>
                    <div class="small text-muted" id="runtime-trend-subtitle"></div>
                </div>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
                <div class="runtime-trend-toolbar mb-3">
                    <div class="btn-group"
                         role="group"
                         aria-label="Khoảng thời gian">
                        <button type="button"
                                class="btn btn-outline-primary runtime-trend-range"
                                data-range="15m">15 phút</button>
                        <button type="button"
                                class="btn btn-outline-primary runtime-trend-range active"
                                data-range="1h">1 giờ</button>
                        <button type="button"
                                class="btn btn-outline-primary runtime-trend-range"
                                data-range="8h">8 giờ</button>
                        <button type="button"
                                class="btn btn-outline-primary runtime-trend-range"
                                data-range="24h">24 giờ</button>
                    </div>

                    <button type="button"
                            class="btn btn-outline-secondary"
                            id="runtime-trend-refresh">
                        Làm mới
                    </button>
                </div>

                <div id="runtime-trend-message"></div>

                <div class="runtime-trend-statistics mb-3">
                    <div class="runtime-trend-stat">
                        <span>Hiện tại</span>
                        <strong id="runtime-trend-current">--</strong>
                    </div>
                    <div class="runtime-trend-stat">
                        <span>Nhỏ nhất</span>
                        <strong id="runtime-trend-min">--</strong>
                    </div>
                    <div class="runtime-trend-stat">
                        <span>Lớn nhất</span>
                        <strong id="runtime-trend-max">--</strong>
                    </div>
                    <div class="runtime-trend-stat">
                        <span>Trung bình</span>
                        <strong id="runtime-trend-avg">--</strong>
                    </div>
                    <div class="runtime-trend-stat">
                        <span>Số điểm</span>
                        <strong id="runtime-trend-count">0</strong>
                    </div>
                </div>

                <div class="runtime-trend-chart-shell">
                    <div class="runtime-trend-loading d-none"
                         id="runtime-trend-loading">
                        Đang tải dữ liệu lịch sử...
                    </div>

                    <svg id="runtime-trend-chart"
                         class="runtime-trend-chart"
                         viewBox="0 0 1100 430"
                         preserveAspectRatio="none"
                         role="img"
                         aria-label="Biểu đồ lịch sử PLC"></svg>

                    <div class="runtime-trend-tooltip d-none"
                         id="runtime-trend-tooltip"></div>
                </div>

                <div class="runtime-trend-legend mt-2">
                    <span class="runtime-trend-legend-item">
                        <i class="runtime-trend-line-sample"></i>
                        Giá trị thực tế
                    </span>
                    <span class="runtime-trend-legend-item">
                        <i class="runtime-trend-limit-sample high"></i>
                        Ngưỡng cao
                    </span>
                    <span class="runtime-trend-legend-item">
                        <i class="runtime-trend-limit-sample low"></i>
                        Ngưỡng thấp
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.monitoringRuntimeConfig = {
    machineCode: @json($machine->code),
    dataUrl: @json(url('/monitoring/' . $machine->code . '/technology/data')),
    trendUrlTemplate: @json(url('/monitoring/' . $machine->code . '/technology/tags/__TAG_ID__/trend')),
    pollInterval: 5000,
    layout: {!! $layoutJson !!}
};
</script>
<script src="{{ asset('js/monitoring-runtime.js') }}"></script>
@endpush
