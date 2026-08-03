@extends('layouts.app')

@section('title', 'Báo cáo tình hình sản xuất ngày')
@section('page_title', 'Báo cáo tình hình sản xuất ngày chi tiết')
@section('page_subtitle', 'Tổng hợp trực tiếp từ các phiếu kiểm nhập sản lượng')

@push('styles')
<style>
    /* Bố cục báo cáo gọn: ưu tiên diện tích hiển thị bảng */
    .compact-filter-card { margin-bottom: .55rem !important; }
    .compact-filter-card .card-body { padding: .55rem .75rem; }
    .compact-filter-form { row-gap: .45rem !important; }
    .compact-filter-form .form-label {
        margin-bottom: .15rem;
        font-size: .72rem;
        font-weight: 600;
        color: #667085;
    }
    .compact-filter-form .form-control,
    .compact-filter-form .form-select,
    .compact-filter-form .btn {
        min-height: 32px;
        padding-top: .25rem;
        padding-bottom: .25rem;
        font-size: .82rem;
    }
    .compact-filter-actions {
        display: flex;
        align-items: end;
        gap: .35rem;
        flex-wrap: nowrap;
    }
    .compact-filter-actions .btn { white-space: nowrap; }

    .summary-strip {
        display: flex;
        gap: .45rem;
        overflow-x: auto;
        padding: .1rem .05rem .45rem;
        margin-bottom: .35rem;
        scrollbar-width: thin;
    }
    .summary-chip {
        min-width: 145px;
        flex: 1 0 145px;
        border: 1px solid #dee2e6;
        border-radius: .45rem;
        background: #fff;
        padding: .38rem .55rem;
        box-shadow: 0 .08rem .25rem rgba(0,0,0,.05);
    }
    .summary-chip .label {
        display: block;
        color: #6c757d;
        font-size: .69rem;
        line-height: 1.1;
        white-space: nowrap;
    }
    .summary-chip .value {
        display: block;
        margin-top: .15rem;
        font-size: .94rem;
        line-height: 1.15;
        font-weight: 700;
        white-space: nowrap;
    }

    .report-card-header { padding: .38rem .75rem; }
    .report-card-header h5 { font-size: .92rem; }
    .report-card-header small { font-size: .72rem; }

    .report-table { font-size: .75rem; white-space: nowrap; }
    .report-table th { vertical-align: middle; text-align: center; background: #eef2f5; padding: .34rem .38rem; }
    .report-table td { vertical-align: middle; padding: .3rem .38rem; }
    .report-table-scroll {
        height: calc(100vh - 245px);
        min-height: 420px;
        overflow: auto;
        position: relative;
        scrollbar-gutter: stable;
    }
    .report-table thead th {
        position: sticky;
        z-index: 20;
        background: #eef2f5;
        box-shadow: inset 0 -1px 0 #cfd6dc;
    }
    .report-table thead tr:first-child th { top: 0; }
    .report-table thead tr:nth-child(2) th {
        top: var(--report-header-row-1-height, 34px);
        z-index: 21;
    }
    .report-table thead th[rowspan="2"] { z-index: 22; }
    .report-table .daily-total td { background: #d9dde1; font-weight: 700; }

    @media (max-width: 1199.98px) {
        .compact-filter-actions { flex-wrap: wrap; }
        .report-table-scroll { height: calc(100vh - 305px); }
    }
    @media print {
        .sidebar, .topbar, .app-footer, .report-actions, .filter-card, .summary-strip { display: none !important; }
        .main-area { margin-left: 0 !important; }
        .content-area { padding: 0 !important; }
        .card { border: 0 !important; box-shadow: none !important; }
        .report-table-scroll { height: auto !important; overflow: visible !important; }
    }
</style>
@endpush

@section('content')

<div class="card shadow-sm filter-card compact-filter-card">
    <div class="card-body">
        <form method="GET" action="{{ route('production-reports.daily-detail') }}" class="row gx-2 compact-filter-form align-items-end">
            <div class="col-6 col-md-2 col-xl-1">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-6 col-md-2 col-xl-1">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <label class="form-label">Máy</label>
                <select name="machine_id" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}" @selected((string)$filters['machine_id'] === (string)$machine->id)>
                            {{ $machine->code }} - {{ $machine->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-3">
                <label class="form-label">Sản phẩm</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">Tất cả</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string)$filters['product_id'] === (string)$product->id)>
                            {{ $product->material_code }} - {{ $product->material_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select form-select-sm">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-xl-3 report-actions compact-filter-actions">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i> Lọc</button>
                <a class="btn btn-success btn-sm" href="{{ route('production-reports.daily-detail.excel', request()->query()) }}">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()"><i class="bi bi-printer"></i> In</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('production-reports.daily-detail') }}">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

@php
    $cards = [
        ['Phiếu sản xuất', number_format($summary['declaration_count'], 0, ',', '.'), 'bi-file-earmark-text'],
        ['Máy sản xuất', number_format($summary['machine_count'], 0, ',', '.'), 'bi-gear-wide-connected'],
        ['Tổng sản lượng', number_format($summary['total_quantity'], 3, ',', '.') . ' ĐVT', 'bi-boxes'],
        ['Chính phẩm', number_format($summary['good_quantity'], 3, ',', '.') . ' ĐVT', 'bi-check-circle'],
        ['Phế phẩm', number_format($summary['reject_quantity'], 3, ',', '.') . ' ĐVT', 'bi-x-circle'],
        ['Phế liệu', number_format($summary['scrap_kg'], 3, ',', '.') . ' kg', 'bi-recycle'],
        ['Trọng lượng', number_format($summary['total_weight_kg'], 3, ',', '.') . ' kg', 'bi-speedometer'],
        ['Giờ dừng', number_format($summary['downtime_minutes'] / 60, 2, ',', '.') . ' giờ', 'bi-stopwatch'],
            ['Bất thường', number_format($summary['issue_count'], 0, ',', '.') . ' dòng', 'bi-exclamation-triangle'],
    ];
@endphp
<div class="summary-strip">
    @foreach($cards as [$label, $value, $icon])
        <div class="summary-chip">
            <span class="label"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span>
            <span class="value">{{ $value }}</span>
        </div>
    @endforeach
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white text-center report-card-header">
        <h5 class="mb-0 fw-bold">TÌNH HÌNH SẢN XUẤT NGÀY CHI TIẾT</h5>
        <small class="text-muted">Từ {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive report-table-scroll" id="dailyReportTableScroll">
            <table class="table table-bordered table-hover report-table mb-0" id="dailyReportTable">
                <thead>
                    <tr>
                        <th rowspan="2">Ngày</th>
                        <th rowspan="2">Mã máy</th>
                        <th rowspan="2">Tên máy</th>
                        <th rowspan="2">Mã sản phẩm</th>
                        <th rowspan="2">Tên sản phẩm</th>
                        <th rowspan="2">ĐVT</th>
                        <th colspan="{{ max(1, $shifts->count()) }}">Số lượng</th>
                        <th rowspan="2">Tổng SL</th>
                        <th rowspan="2">Chính phẩm</th>
                        <th rowspan="2">Phế phẩm</th>
                        <th rowspan="2">Phế liệu kg</th>
                        <th rowspan="2">Số m/ĐVT</th>
                        <th rowspan="2">KL chuẩn g/ĐVT</th>
                        <th colspan="{{ max(1, $shifts->count()) }}">Trọng lượng kg</th>
                        <th rowspan="2">Tổng KL kg</th>
                        <th rowspan="2">Giờ dừng</th>
                        <th rowspan="2">Mã nguyên nhân</th>
                        <th rowspan="2">Nguyên nhân dừng</th>
                        <th rowspan="2">Ghi chú</th>
                        <th rowspan="2">Kiểm tra dữ liệu</th>
                        <th rowspan="2">Phiếu nguồn</th>
                    </tr>
                    <tr>
                        @forelse($shifts as $shift)<th>{{ $shift->name ?: $shift->code }}</th>@empty<th>Ca</th>@endforelse
                        @forelse($shifts as $shift)<th>{{ $shift->name ?: $shift->code }}</th>@empty<th>Ca</th>@endforelse
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows->groupBy(fn($row) => $row['date']->format('Y-m-d')) as $dateKey => $dateRows)
                        @foreach($dateRows as $row)
                            <tr>
                                <td>{{ $row['date']->format('d/m/Y') }}</td>
                                <td>{{ $row['machine_code'] }}</td>
                                <td>{{ $row['machine_name'] }}</td>
                                <td>{{ $row['product_code'] }}</td>
                                <td class="text-wrap" style="min-width:180px">{{ $row['product_name'] }}</td>
                                <td class="text-center">{{ $row['unit'] }}</td>
                                @foreach($shifts as $shift)<td class="text-end">{{ number_format($row['quantities'][$shift->id] ?? 0, 3, ',', '.') }}</td>@endforeach
                                <td class="text-end fw-semibold">{{ number_format($row['total_quantity'], 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['good_quantity'], 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['reject_quantity'], 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['scrap_kg'], 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['actual_length_m'], 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['standard_weight_g'], 3, ',', '.') }}</td>
                                @foreach($shifts as $shift)<td class="text-end">{{ number_format($row['weights'][$shift->id] ?? 0, 3, ',', '.') }}</td>@endforeach
                                <td class="text-end fw-semibold">{{ number_format($row['total_weight_kg'], 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['downtime_minutes'] / 60, 2, ',', '.') }}</td>
                                <td>{{ implode(', ', $row['downtime_codes']) }}</td>
                                <td class="text-wrap" style="min-width:180px">{{ implode('; ', $row['downtime_reasons']) }}</td>
                                <td class="text-wrap" style="min-width:180px">{{ implode('; ', $row['notes']) }}</td>
                                <td class="text-wrap" style="min-width:220px">
                                    @forelse($row['issues'] as $issue)
                                        <span class="badge text-bg-warning me-1 mb-1">{{ $issue }}</span>
                                    @empty
                                        <span class="badge text-bg-success">Hợp lệ</span>
                                    @endforelse
                                </td>
                                <td class="text-nowrap">
                                    @foreach($row['declaration_ids'] as $declarationId)
                                        <a class="btn btn-sm btn-outline-primary mb-1" href="{{ route('production-declarations.show', $declarationId) }}" title="Mở phiếu #{{ $declarationId }}">#{{ $declarationId }}</a>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                        @php($total = $dailyTotals[$dateKey])
                        <tr class="daily-total">
                            <td colspan="6" class="text-end">Tổng ngày {{ $total['date']->format('d/m/Y') }}</td>
                            @foreach($shifts as $shift)<td class="text-end">{{ number_format($total['quantities'][$shift->id] ?? 0, 3, ',', '.') }}</td>@endforeach
                            <td class="text-end">{{ number_format($total['total_quantity'], 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($total['good_quantity'], 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($total['reject_quantity'], 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($total['scrap_kg'], 3, ',', '.') }}</td>
                            <td colspan="2"></td>
                            @foreach($shifts as $shift)<td class="text-end">{{ number_format($total['weights'][$shift->id] ?? 0, 3, ',', '.') }}</td>@endforeach
                            <td class="text-end">{{ number_format($total['total_weight_kg'], 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($total['downtime_minutes'] / 60, 2, ',', '.') }}</td>
                            <td colspan="5"></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 20 + ($shifts->count() * 2) }}" class="text-center py-5 text-muted">Không có dữ liệu phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('dailyReportTable');
        if (!table || !table.tHead || !table.tHead.rows.length) return;

        const updateStickyHeaderOffset = function () {
            const firstHeaderRow = table.tHead.rows[0];
            table.style.setProperty('--report-header-row-1-height', firstHeaderRow.getBoundingClientRect().height + 'px');
        };

        updateStickyHeaderOffset();
        window.addEventListener('resize', updateStickyHeaderOffset);
    });
</script>
@endpush

@endsection
