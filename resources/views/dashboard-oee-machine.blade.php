@extends('layouts.app')

@section('title', 'Chi tiết OEE ' . $machine->code)
@section('page_title', 'Chi tiết OEE máy ' . $machine->code)
@section('page_subtitle', $machine->name . ' · Truy ngược theo ngày, ca và phiếu sản xuất')

@section('content')
@php
    $formatPercent = static fn (?float $value): string =>
        $value === null ? '—' : number_format($value, 2) . '%';

    $statusColor = static function (?float $value): string {
        if ($value === null) return '#94a3b8';
        if ($value >= 85) return '#22a06b';
        if ($value >= 70) return '#e0a400';
        return '#d64545';
    };

    $trendPoints = $dailyTrend->values()->map(function ($row, $index) use ($dailyTrend) {
        $count = max($dailyTrend->count() - 1, 1);
        $x = 24 + ($index / $count) * 752;
        $y = 180 - (($row['oee'] ?? 0) / 100) * 150;
        return round($x, 2) . ',' . round($y, 2);
    })->implode(' ');
@endphp

@push('styles')
<style>
    .machine-detail-card {
        border: 1px solid #dfe6ee;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 3px 14px rgba(15, 23, 42, .06);
        padding: 14px;
    }

    .machine-identity {
        border-radius: 14px;
        background: linear-gradient(135deg, #0b6cad, #1596df);
        color: #fff;
        padding: 16px;
    }

    .detail-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(140px, 1fr));
        gap: 10px;
    }

    .detail-kpi {
        border-radius: 12px;
        padding: 13px;
        color: #fff;
    }

    .detail-kpi span {
        display: block;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        opacity: .85;
    }

    .detail-kpi strong {
        display: block;
        font-size: 1.55rem;
        margin-top: 3px;
    }

    .detail-kpi-oee { background: #1677c8; }
    .detail-kpi-a { background: #1f9d72; }
    .detail-kpi-p { background: #d89d0b; }
    .detail-kpi-q { background: #7468c7; }

    .section-title {
        font-weight: 800;
        color: #172033;
        margin-bottom: 12px;
    }

    .trend-svg {
        width: 100%;
        min-height: 220px;
    }

    .trend-grid {
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

    .trend-dot {
        fill: #fff;
        stroke: #1677c8;
        stroke-width: 3;
    }

    .metric-pill {
        min-width: 72px;
        display: inline-block;
        text-align: center;
        border-radius: 999px;
        color: #fff;
        font-weight: 800;
        padding: 4px 8px;
    }

    .reason-cell {
        max-width: 420px;
        white-space: normal;
        line-height: 1.35;
    }

    .shift-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
    }

    .shift-box {
        border: 1px solid #dfe6ee;
        border-radius: 12px;
        padding: 12px;
    }

    .shift-box strong {
        font-size: 1.25rem;
    }

    @media (max-width: 767.98px) {
        .detail-kpis {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media print {
        .sidebar,
        .topbar,
        .no-print {
            display: none !important;
        }

        .main-wrapper {
            margin-left: 0 !important;
        }
    }
</style>
@endpush

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
    <a href="{{ route('dashboard', [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'production_shift_id' => $filters['production_shift_id'],
        ]) }}"
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Dashboard tổng
    </a>

    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="small text-muted">Từ ngày</label>
            <input type="date"
                   name="date_from"
                   value="{{ $filters['date_from'] }}"
                   class="form-control form-control-sm">
        </div>
        <div>
            <label class="small text-muted">Đến ngày</label>
            <input type="date"
                   name="date_to"
                   value="{{ $filters['date_to'] }}"
                   class="form-control form-control-sm">
        </div>
        <div>
            <label class="small text-muted">Ca</label>
            <select name="production_shift_id"
                    class="form-select form-select-sm">
                <option value="">Tất cả</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}"
                        @selected($filters['production_shift_id'] == $shift->id)>
                        {{ $shift->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-primary">
            <i class="bi bi-funnel-fill"></i>
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-secondary"
                onclick="window.print()">
            <i class="bi bi-printer"></i>
        </button>
    </form>
</div>

<div class="machine-identity mb-3">
    <div class="row align-items-center g-3">
        <div class="col-md-7">
            <div class="text-uppercase small opacity-75">
                Máy sản xuất
            </div>
            <h2 class="mb-1">{{ $machine->code }} - {{ $machine->name }}</h2>
            <div class="opacity-75">
                {{ $machine->productionLine?->plant?->name ?? 'Chưa xác định nhà máy' }}
                · {{ $machine->productionLine?->name ?? 'Chưa xác định dây chuyền' }}
            </div>
        </div>
        <div class="col-md-5 text-md-end">
            <div>
                {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}
                @if($filters['date_from'] !== $filters['date_to'])
                    – {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}
                @endif
            </div>
            <div class="small opacity-75">
                Cập nhật {{ $lastUpdatedAt->format('H:i:s') }}
            </div>
        </div>
    </div>
</div>

<div class="detail-kpis mb-3">
    <div class="detail-kpi detail-kpi-oee">
        <span>OEE trung bình</span>
        <strong>{{ $formatPercent($kpis['oee']) }}</strong>
    </div>
    <div class="detail-kpi detail-kpi-a">
        <span>Availability</span>
        <strong>{{ $formatPercent($kpis['availability']) }}</strong>
    </div>
    <div class="detail-kpi detail-kpi-p">
        <span>Performance</span>
        <strong>{{ $formatPercent($kpis['performance']) }}</strong>
    </div>
    <div class="detail-kpi detail-kpi-q">
        <span>Quality</span>
        <strong>{{ $formatPercent($kpis['quality']) }}</strong>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="machine-detail-card h-100">
            <div class="section-title">Xu hướng OEE theo ngày</div>

            @if($dailyTrend->count() > 1)
                <svg class="trend-svg" viewBox="0 0 800 220"
                     aria-label="Xu hướng OEE máy">
                    @foreach([0, 25, 50, 75, 100] as $tick)
                        @php($y = 180 - ($tick / 100) * 150)
                        <line x1="24"
                              y1="{{ $y }}"
                              x2="776"
                              y2="{{ $y }}"
                              class="trend-grid"/>
                        <text x="2"
                              y="{{ $y + 4 }}"
                              font-size="10"
                              fill="#64748b">
                            {{ $tick }}
                        </text>
                    @endforeach

                    <polyline points="{{ $trendPoints }}"
                              class="trend-line"/>

                    @foreach($dailyTrend as $index => $row)
                        @php
                            $count = max($dailyTrend->count() - 1, 1);
                            $x = 24 + ($index / $count) * 752;
                            $y = 180 - (($row['oee'] ?? 0) / 100) * 150;
                        @endphp
                        <circle cx="{{ $x }}"
                                cy="{{ $y }}"
                                r="5"
                                class="trend-dot">
                            <title>
                                {{ $row['label'] }}:
                                OEE {{ $formatPercent($row['oee']) }},
                                A {{ $formatPercent($row['availability']) }},
                                P {{ $formatPercent($row['performance']) }},
                                Q {{ $formatPercent($row['quality']) }}
                            </title>
                        </circle>
                        <text x="{{ $x }}"
                              y="205"
                              text-anchor="middle"
                              font-size="10"
                              fill="#64748b">
                            {{ $row['label'] }}
                        </text>
                    @endforeach
                </svg>
            @else
                <div class="text-center text-muted py-5">
                    Cần ít nhất hai ngày có OEE để vẽ xu hướng.
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="machine-detail-card h-100">
            <div class="section-title">OEE theo ca</div>
            <div class="shift-summary">
                @forelse($shiftSummary as $row)
                    <div class="shift-box">
                        <div class="small text-muted">
                            {{ $row['shift']?->name }}
                            · {{ $row['count'] }} phiếu
                        </div>
                        <strong style="color: {{ $statusColor($row['oee']) }}">
                            {{ $formatPercent($row['oee']) }}
                        </strong>
                        <div class="small mt-2">
                            A {{ $formatPercent($row['availability']) }}
                            · P {{ $formatPercent($row['performance']) }}
                            · Q {{ $formatPercent($row['quality']) }}
                        </div>
                    </div>
                @empty
                    <div class="text-muted">
                        Chưa có ca tính được OEE.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="machine-detail-card">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="section-title mb-0">
            Chi tiết các phiếu và trạng thái dữ liệu
        </div>
        <span class="badge text-bg-light border">
            {{ $declarationRows->count() }} phiếu
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Ngày / Ca</th>
                    <th>Sản phẩm</th>
                    <th class="text-end">Sản lượng</th>
                    <th class="text-end">Trọng lượng</th>
                    <th class="text-end">MD264 ổn định</th>
                    <th class="text-end">Định mức</th>
                    <th class="text-center">A</th>
                    <th class="text-center">P</th>
                    <th class="text-center">Q</th>
                    <th class="text-center">OEE</th>
                    <th>Trạng thái</th>
                    <th>Nguyên nhân / chẩn đoán</th>
                    <th class="text-end">Phiếu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($declarationRows as $row)
                    @php($declaration = $row['declaration'])
                    <tr>
                        <td class="text-nowrap">
                            <strong>
                                {{ $declaration->production_date?->format('d/m/Y') }}
                            </strong>
                            <div class="small text-muted">
                                {{ $declaration->shift?->name }}
                                · #{{ $declaration->id }}
                            </div>
                        </td>
                        <td class="reason-cell">
                            {{ $row['products'] ?: '—' }}
                        </td>
                        <td class="text-end">
                            {{ number_format((float) $declaration->total_quantity, 3) }}
                        </td>
                        <td class="text-end">
                            {{ number_format($row['total_kg'], 2) }} kg
                        </td>
                        <td class="text-end">
                            {{ $row['stable_rate'] !== null
                                ? number_format($row['stable_rate'], 2) . ' kg/h'
                                : '—' }}
                        </td>
                        <td class="text-end">
                            {{ $row['standard_rate'] !== null
                                ? number_format($row['standard_rate'], 2) . ' kg/h'
                                : '—' }}
                        </td>
                        @foreach([
                            $declaration->availability,
                            $declaration->performance,
                            $declaration->quality_rate,
                            $declaration->oee,
                        ] as $metric)
                            <td class="text-center">
                                <span class="metric-pill"
                                      style="background: {{ $statusColor(
                                          $metric !== null
                                              ? (float) $metric * 100
                                              : null
                                      ) }}">
                                    {{ $metric !== null
                                        ? number_format((float) $metric * 100, 2) . '%'
                                        : '—' }}
                                </span>
                            </td>
                        @endforeach
                        <td>
                            <span class="badge text-bg-{{ $row['data_status']['class'] }}">
                                {{ $row['data_status']['label'] }}
                            </span>
                        </td>
                        <td class="reason-cell small">
                            {{ $declaration->calculation_note ?: 'Chưa có chẩn đoán.' }}
                        </td>
                        <td class="text-end text-nowrap no-print">
                            <a href="{{ route('production-declarations.show', $declaration) }}"
                               class="btn btn-sm btn-outline-primary">
                                Mở
                            </a>
                            @can('production-declarations.view')
                                <form method="POST"
                                      action="{{ route('production-declarations.recalculate-oee', $declaration) }}"
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success"
                                            title="Tính lại OEE">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13"
                            class="text-center text-muted py-5">
                            Máy chưa có phiếu sản xuất trong kỳ đã chọn.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
