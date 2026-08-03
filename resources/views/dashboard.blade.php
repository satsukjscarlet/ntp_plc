@extends('layouts.app')

@section('title', 'Dashboard OEE - NTP_PLC')
@section('page_title', 'Dashboard OEE điều hành sản xuất')
@section('page_subtitle', 'Tổng hợp A, P, Q và OEE theo dữ liệu PLC và phiếu sản xuất')

@section('content')
@php
    $scoreClass = static function (?float $value): string {
        if ($value === null) return 'score-neutral';
        if ($value >= 85) return 'score-good';
        if ($value >= 70) return 'score-warning';
        return 'score-danger';
    };

    $formatPercent = static fn (?float $value): string =>
        $value === null ? '—' : number_format($value, 2) . '%';

    $trendPoints = $dailyTrend->values()->map(function ($row, $index) use ($dailyTrend) {
        $count = max($dailyTrend->count() - 1, 1);
        $x = 20 + ($index / $count) * 760;
        $y = 180 - (($row['oee'] ?? 0) / 100) * 150;
        return round($x, 2) . ',' . round($y, 2);
    })->implode(' ');
@endphp

@push('styles')
<style>
    .executive-dashboard {
        --oee-green: #22a06b;
        --oee-yellow: #e0a400;
        --oee-red: #d64545;
        --oee-blue: #1677c8;
        --oee-ink: #172033;
        --oee-soft: #f2f6fa;
    }

    .dashboard-toolbar,
    .executive-card,
    .machine-oee-card {
        border: 1px solid #dfe6ee;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 3px 14px rgba(15, 23, 42, .06);
    }

    .dashboard-toolbar {
        padding: 12px;
        margin-bottom: 14px;
    }

    .dashboard-toolbar .form-label {
        font-size: .72rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 3px;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .kpi-card {
        padding: 14px 16px;
        color: #fff;
        border-radius: 14px;
        min-height: 108px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        overflow: hidden;
        position: relative;
    }

    .kpi-card::after {
        content: "";
        position: absolute;
        width: 92px;
        height: 92px;
        border: 18px solid rgba(255,255,255,.12);
        border-radius: 50%;
        right: -18px;
        top: -26px;
    }

    .kpi-oee { background: linear-gradient(135deg, #1368aa, #2196d7); }
    .kpi-a { background: linear-gradient(135deg, #14855e, #29b37f); }
    .kpi-p { background: linear-gradient(135deg, #c88400, #f2b625); }
    .kpi-q { background: linear-gradient(135deg, #5f54b5, #8778dc); }

    .kpi-label {
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        opacity: .88;
    }

    .kpi-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 5px;
    }

    .kpi-note {
        font-size: .72rem;
        opacity: .85;
        margin-top: 5px;
    }

    .executive-card {
        padding: 14px;
        height: 100%;
    }

    .section-heading {
        font-size: .98rem;
        font-weight: 800;
        color: var(--oee-ink);
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .machine-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(370px, 1fr));
        gap: 14px;
    }

    .machine-oee-card {
        overflow: hidden;
    }

    .machine-card-header {
        background: linear-gradient(90deg, #0879df, #1796e9);
        color: #fff;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .machine-card-body {
        padding: 14px;
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 16px;
    }

    .gauge {
        --gauge-value: 0;
        --gauge-color: #1677c8;
        width: 134px;
        height: 134px;
        border-radius: 50%;
        background:
            conic-gradient(
                var(--gauge-color) calc(var(--gauge-value) * 1%),
                #e8eef4 0
            );
        display: grid;
        place-items: center;
        position: relative;
        margin: 0 auto;
    }

    .gauge::before {
        content: "";
        width: 98px;
        height: 98px;
        border-radius: 50%;
        background: #fff;
        position: absolute;
    }

    .gauge-content {
        position: relative;
        text-align: center;
        z-index: 1;
    }

    .gauge-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--oee-ink);
    }

    .gauge-label {
        font-size: .7rem;
        color: #64748b;
        font-weight: 700;
    }

    .score-good { --score-color: var(--oee-green); }
    .score-warning { --score-color: var(--oee-yellow); }
    .score-danger { --score-color: var(--oee-red); }
    .score-neutral { --score-color: #94a3b8; }

    .metric-row {
        display: grid;
        grid-template-columns: 98px minmax(0, 1fr) 64px;
        gap: 8px;
        align-items: center;
        margin-bottom: 8px;
        font-size: .78rem;
    }

    .metric-label {
        font-weight: 700;
        color: #475569;
    }

    .metric-track {
        height: 12px;
        border-radius: 999px;
        background: #e8eef4;
        overflow: hidden;
    }

    .metric-fill {
        height: 100%;
        width: calc(min(var(--metric-value), 100) * 1%);
        border-radius: 999px;
        background: var(--metric-color);
    }

    .metric-number {
        text-align: right;
        font-weight: 800;
        color: var(--oee-ink);
    }

    .machine-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px 16px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed #d9e2ec;
        font-size: .76rem;
    }

    .machine-facts span {
        color: #64748b;
    }

    .machine-facts strong {
        display: block;
        color: #1e293b;
        word-break: break-word;
    }

    .trend-svg {
        width: 100%;
        min-height: 220px;
    }

    .trend-grid-line {
        stroke: #dce5ee;
        stroke-width: 1;
    }

    .trend-line {
        fill: none;
        stroke: #1677c8;
        stroke-width: 4;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .trend-area {
        fill: url(#trendGradient);
    }

    .trend-dot {
        fill: #fff;
        stroke: #1677c8;
        stroke-width: 3;
    }

    .rank-row,
    .shift-row {
        display: grid;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .rank-row {
        grid-template-columns: 32px minmax(0, 1fr) 70px;
    }

    .shift-row {
        grid-template-columns: 90px minmax(0, 1fr) 70px;
    }

    .rank-index {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: grid;
        place-items: center;
        font-weight: 800;
        background: #edf4fa;
        color: #1677c8;
    }

    .compact-track {
        height: 9px;
        background: #e8eef4;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 4px;
    }

    .compact-fill {
        height: 100%;
        border-radius: inherit;
        width: calc(min(var(--value), 100) * 1%);
        background: var(--color, #1677c8);
    }

    .summary-strip {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 1px;
        background: #dfe6ee;
        border: 1px solid #dfe6ee;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 14px;
    }

    .summary-item {
        background: #fff;
        padding: 9px 12px;
    }

    .summary-item span {
        display: block;
        font-size: .68rem;
        color: #64748b;
        font-weight: 700;
    }

    .summary-item strong {
        color: #1e293b;
        font-size: .95rem;
    }

    .empty-dashboard {
        padding: 54px 20px;
        text-align: center;
        color: #64748b;
    }

    @media (max-width: 991.98px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .summary-strip {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 575.98px) {
        .kpi-grid,
        .summary-strip {
            grid-template-columns: 1fr 1fr;
        }

        .machine-grid {
            grid-template-columns: 1fr;
        }

        .machine-card-body {
            grid-template-columns: 1fr;
        }

        .machine-facts {
            grid-template-columns: 1fr;
        }

        .kpi-value {
            font-size: 1.6rem;
        }
    }

    @media print {
        .sidebar,
        .topbar,
        .dashboard-toolbar,
        .no-print {
            display: none !important;
        }

        .main-wrapper {
            margin-left: 0 !important;
        }

        .machine-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>
@endpush

<div class="executive-dashboard">
    <form method="GET" class="dashboard-toolbar no-print">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from"
                       value="{{ $filters['date_from'] }}"
                       class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to"
                       value="{{ $filters['date_to'] }}"
                       class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Nhà máy</label>
                <select name="plant_id" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    @foreach($plants as $plant)
                        <option value="{{ $plant->id }}"
                            @selected($filters['plant_id'] == $plant->id)>
                            {{ $plant->code }} - {{ $plant->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Dây chuyền</label>
                <select name="production_line_id" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}"
                            @selected($filters['production_line_id'] == $line->id)>
                            {{ $line->code }} - {{ $line->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Máy</label>
                <select name="machine_id" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}"
                            @selected($filters['machine_id'] == $machine->id)>
                            {{ $machine->code }} - {{ $machine->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label">Ca</label>
                <select name="production_shift_id" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}"
                            @selected($filters['production_shift_id'] == $shift->id)>
                            {{ $shift->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel-fill"></i>
                </button>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="small text-muted">
            Dữ liệu {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}
            @if($filters['date_to'] !== $filters['date_from'])
                – {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}
            @endif
            · Cập nhật {{ $lastUpdatedAt->format('H:i:s') }}
        </div>
        <div class="d-flex gap-2 no-print">
            <span class="badge text-bg-light border">Mục tiêu OEE {{ number_format($targetOee, 0) }}%</span>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="window.print()">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>

    @if($kpis['declaration_count'] === 0)
        <div class="executive-card empty-dashboard">
            <i class="bi bi-speedometer2 fs-1"></i>
            <h5 class="mt-3">Chưa có kết quả OEE trong phạm vi đã chọn</h5>
            <p class="mb-0">
                Hãy tính lại OEE cho các phiếu sản xuất hoặc thay đổi khoảng ngày.
            </p>
        </div>
    @else
        <div class="kpi-grid">
            <div class="kpi-card kpi-oee">
                <div>
                    <div class="kpi-label">OEE trung bình</div>
                    <div class="kpi-value">{{ $formatPercent($kpis['oee']) }}</div>
                    <div class="kpi-note">
                        {{ $kpis['declaration_count'] }} ca · {{ $kpis['machine_count'] }} máy
                    </div>
                </div>
                <i class="bi bi-speedometer2 fs-1 opacity-75"></i>
            </div>
            <div class="kpi-card kpi-a">
                <div>
                    <div class="kpi-label">Availability</div>
                    <div class="kpi-value">{{ $formatPercent($kpis['availability']) }}</div>
                    <div class="kpi-note">MD488 của ca kế tiếp</div>
                </div>
                <i class="bi bi-clock-history fs-1 opacity-75"></i>
            </div>
            <div class="kpi-card kpi-p">
                <div>
                    <div class="kpi-label">Performance</div>
                    <div class="kpi-value">{{ $formatPercent($kpis['performance']) }}</div>
                    <div class="kpi-note">MD264 ổn định / định mức</div>
                </div>
                <i class="bi bi-graph-up-arrow fs-1 opacity-75"></i>
            </div>
            <div class="kpi-card kpi-q">
                <div>
                    <div class="kpi-label">Quality</div>
                    <div class="kpi-value">{{ $formatPercent($kpis['quality']) }}</div>
                    <div class="kpi-note">Chính phẩm / tổng sản lượng</div>
                </div>
                <i class="bi bi-patch-check-fill fs-1 opacity-75"></i>
            </div>
        </div>

        <div class="summary-strip">
            <div class="summary-item">
                <span>TỔNG SẢN LƯỢNG</span>
                <strong>{{ number_format($kpis['total_quantity'], 3) }} ĐVT</strong>
            </div>
            <div class="summary-item">
                <span>CHÍNH PHẨM</span>
                <strong>{{ number_format($kpis['total_good'], 3) }} ĐVT</strong>
            </div>
            <div class="summary-item">
                <span>PHẾ PHẨM</span>
                <strong>{{ number_format($kpis['total_reject'], 3) }} ĐVT</strong>
            </div>
            <div class="summary-item">
                <span>TRỌNG LƯỢNG</span>
                <strong>{{ number_format($kpis['total_kg'], 2) }} kg</strong>
            </div>
            <div class="summary-item">
                <span>PHẾ LIỆU</span>
                <strong>{{ number_format($kpis['scrap_kg'], 2) }} kg</strong>
            </div>
            <div class="summary-item">
                <span>PHIẾU ĐÃ TÍNH OEE</span>
                <strong>{{ number_format($kpis['declaration_count']) }}</strong>
            </div>
        </div>

        <div class="machine-grid mb-3">
            @foreach($machineCards as $card)
                @php
                    $oeeColor = ($card['oee'] ?? 0) >= 85
                        ? '#22a06b'
                        : (($card['oee'] ?? 0) >= 70 ? '#e0a400' : '#d64545');
                @endphp
                <div class="machine-oee-card">
                    <div class="machine-card-header">
                        <div>
                            <strong>{{ $card['machine']->code }}</strong>
                            <span class="ms-1">{{ $card['machine']->name }}</span>
                        </div>
                        <span class="badge bg-light text-dark">
                            {{ $card['latest']->shift?->name ?? '—' }}
                        </span>
                    </div>
                    <div class="machine-card-body">
                        <div>
                            <div class="gauge"
                                 style="--gauge-value: {{ $card['oee'] ?? 0 }}; --gauge-color: {{ $oeeColor }};">
                                <div class="gauge-content">
                                    <div class="gauge-value">{{ $formatPercent($card['oee']) }}</div>
                                    <div class="gauge-label">OEE</div>
                                </div>
                            </div>
                            <div class="text-center small mt-2">
                                <span class="badge {{ ($card['oee'] ?? 0) >= $targetOee ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ ($card['oee'] ?? 0) >= $targetOee ? 'Đạt mục tiêu' : 'Chưa đạt' }}
                                </span>
                            </div>
                        </div>

                        <div>
                            @foreach([
                                ['label' => 'Availability', 'value' => $card['availability'], 'color' => '#1da67a'],
                                ['label' => 'Performance', 'value' => $card['performance'], 'color' => '#e3aa10'],
                                ['label' => 'Quality', 'value' => $card['quality'], 'color' => '#31a956'],
                            ] as $metric)
                                <div class="metric-row">
                                    <div class="metric-label">{{ $metric['label'] }}</div>
                                    <div class="metric-track">
                                        <div class="metric-fill"
                                             style="--metric-value: {{ $metric['value'] ?? 0 }}; --metric-color: {{ $metric['color'] }};"></div>
                                    </div>
                                    <div class="metric-number">{{ $formatPercent($metric['value']) }}</div>
                                </div>
                            @endforeach

                            <div class="machine-facts">
                                <div>
                                    <span>Sản phẩm gần nhất</span>
                                    <strong>{{ $card['products'] ?: 'Chưa có' }}</strong>
                                </div>
                                <div>
                                    <span>Ngày / ca gần nhất</span>
                                    <strong>
                                        {{ $card['latest']->production_date?->format('d/m/Y') }}
                                        · {{ $card['latest']->shift?->name }}
                                    </strong>
                                </div>
                                <div>
                                    <span>Năng suất hiện tại</span>
                                    <strong>
                                        {{ $card['actual_stable_rate'] !== null
                                            ? number_format($card['actual_stable_rate'], 2) . ' kg/h'
                                            : 'Chưa có' }}
                                    </strong>
                                </div>
                                <div>
                                    <span>Năng suất định mức</span>
                                    <strong>
                                        {{ $card['weighted_standard_rate'] !== null
                                            ? number_format($card['weighted_standard_rate'], 2) . ' kg/h'
                                            : 'Chưa có' }}
                                    </strong>
                                </div>
                                <div>
                                    <span>Trọng lượng kỳ chọn</span>
                                    <strong>{{ number_format($card['total_kg'], 2) }} kg</strong>
                                </div>
                                <div>
                                    <span>Số ca đã tính</span>
                                    <strong>{{ $card['declaration_count'] }}</strong>
                                </div>
                            </div>

                            <div class="mt-3 text-end no-print">
                                <a href="{{ route('dashboard.oee.machine', [
                                        'machine' => $card['machine']->code,
                                        'date_from' => $filters['date_from'],
                                        'date_to' => $filters['date_to'],
                                        'production_shift_id' => $filters['production_shift_id'],
                                    ]) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-bar-chart-line me-1"></i>Chi tiết OEE máy
                                </a>
                                <a href="{{ route('production-declarations.show', $card['latest']) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Phiếu gần nhất
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="executive-card">
                    <div class="section-heading">
                        <span>Xu hướng OEE theo ngày</span>
                        <small class="text-muted fw-normal">{{ $dailyTrend->count() }} ngày</small>
                    </div>

                    @if($dailyTrend->count() > 1)
                        <svg class="trend-svg" viewBox="0 0 800 220" role="img"
                             aria-label="Xu hướng OEE">
                            <defs>
                                <linearGradient id="trendGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#1677c8" stop-opacity=".28"/>
                                    <stop offset="100%" stop-color="#1677c8" stop-opacity=".02"/>
                                </linearGradient>
                            </defs>

                            @foreach([0, 25, 50, 75, 100] as $tick)
                                @php($y = 180 - ($tick / 100) * 150)
                                <line x1="20" y1="{{ $y }}" x2="780" y2="{{ $y }}"
                                      class="trend-grid-line"/>
                                <text x="2" y="{{ $y + 4 }}" font-size="10" fill="#64748b">
                                    {{ $tick }}
                                </text>
                            @endforeach

                            <polygon points="20,180 {{ $trendPoints }} 780,180"
                                     class="trend-area"/>
                            <polyline points="{{ $trendPoints }}" class="trend-line"/>

                            @foreach($dailyTrend as $index => $row)
                                @php
                                    $count = max($dailyTrend->count() - 1, 1);
                                    $x = 20 + ($index / $count) * 760;
                                    $y = 180 - (($row['oee'] ?? 0) / 100) * 150;
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="5" class="trend-dot">
                                    <title>{{ $row['label'] }}: {{ $formatPercent($row['oee']) }}</title>
                                </circle>
                                <text x="{{ $x }}" y="205" text-anchor="middle"
                                      font-size="10" fill="#64748b">
                                    {{ $row['label'] }}
                                </text>
                            @endforeach
                        </svg>
                    @else
                        <div class="empty-dashboard py-4">
                            Cần ít nhất hai ngày dữ liệu để hiển thị xu hướng.
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="executive-card">
                    <div class="section-heading">OEE theo ca</div>

                    @forelse($shiftSummary as $row)
                        @php
                            $color = ($row['oee'] ?? 0) >= 85
                                ? '#22a06b'
                                : (($row['oee'] ?? 0) >= 70 ? '#e0a400' : '#d64545');
                        @endphp
                        <div class="shift-row">
                            <strong>{{ $row['shift']?->name ?? 'Ca' }}</strong>
                            <div>
                                <div class="compact-track">
                                    <div class="compact-fill"
                                         style="--value: {{ $row['oee'] ?? 0 }}; --color: {{ $color }};"></div>
                                </div>
                                <small class="text-muted">{{ $row['count'] }} phiếu</small>
                            </div>
                            <strong class="text-end">{{ $formatPercent($row['oee']) }}</strong>
                        </div>
                    @empty
                        <div class="text-muted">Chưa có dữ liệu theo ca.</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($missingMachines->isNotEmpty())
            <div class="executive-card mb-3">
                <div class="section-heading">
                    <span>
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                        Máy chưa có OEE trong kỳ
                    </span>
                    <span class="badge text-bg-warning">
                        {{ $missingMachines->count() }} máy
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Máy</th>
                                <th>Dây chuyền</th>
                                <th>Trạng thái dữ liệu</th>
                                <th>Phiếu gần nhất</th>
                                <th class="text-end">Kiểm tra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missingMachines as $row)
                                <tr>
                                    <td>
                                        <strong>{{ $row['machine']->code }}</strong>
                                        <div class="small text-muted">
                                            {{ $row['machine']->name }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $row['machine']->productionLine?->name ?? '—' }}
                                    </td>
                                    <td>
                                        <span class="badge text-bg-{{ $row['status']['class'] }}">
                                            {{ $row['status']['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($row['declaration'])
                                            #{{ $row['declaration']->id }}
                                            · {{ $row['declaration']->production_date?->format('d/m/Y') }}
                                            · {{ $row['declaration']->shift?->name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('dashboard.oee.machine', [
                                                'machine' => $row['machine']->code,
                                                'date_from' => $filters['date_from'],
                                                'date_to' => $filters['date_to'],
                                                'production_shift_id' => $filters['production_shift_id'],
                                            ]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Xem nguyên nhân
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="executive-card">
                    <div class="section-heading">
                        <span>Máy có OEE cao nhất</span>
                        <i class="bi bi-trophy-fill text-warning"></i>
                    </div>
                    @foreach($ranking['best'] as $index => $card)
                        <div class="rank-row">
                            <div class="rank-index">{{ $index + 1 }}</div>
                            <div>
                                <strong>{{ $card['machine']->code }} - {{ $card['machine']->name }}</strong>
                                <div class="compact-track">
                                    <div class="compact-fill"
                                         style="--value: {{ $card['oee'] ?? 0 }}; --color: #22a06b;"></div>
                                </div>
                            </div>
                            <strong class="text-end">{{ $formatPercent($card['oee']) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-6">
                <div class="executive-card">
                    <div class="section-heading">
                        <span>Máy cần ưu tiên cải thiện</span>
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    </div>
                    @foreach($ranking['worst'] as $index => $card)
                        <div class="rank-row">
                            <div class="rank-index">{{ $index + 1 }}</div>
                            <div>
                                <strong>{{ $card['machine']->code }} - {{ $card['machine']->name }}</strong>
                                <div class="compact-track">
                                    <div class="compact-fill"
                                         style="--value: {{ $card['oee'] ?? 0 }}; --color: #d64545;"></div>
                                </div>
                            </div>
                            <strong class="text-end">{{ $formatPercent($card['oee']) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    window.setTimeout(() => {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 60000);
</script>
@endpush
@endsection
