<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;font-family:Arial;font-size:10pt}th,td{border:1px solid #777;padding:4px}th{background:#e7ecef;font-weight:bold;text-align:center}.total{background:#d9dde1;font-weight:bold}.num{text-align:right}</style></head><body>
<h2 style="text-align:center">TÌNH HÌNH SẢN XUẤT NGÀY CHI TIẾT</h2>
<p style="text-align:center">Từ {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</p>
<table>
<tr><th rowspan="2">Ngày</th><th rowspan="2">Mã máy</th><th rowspan="2">Tên máy</th><th rowspan="2">Mã SP</th><th rowspan="2">Tên SP</th><th rowspan="2">ĐVT</th><th colspan="{{ max(1,$shifts->count()) }}">Số lượng</th><th rowspan="2">Tổng SL</th><th rowspan="2">Chính phẩm</th><th rowspan="2">Phế phẩm</th><th rowspan="2">Phế liệu kg</th><th rowspan="2">Số m/ĐVT</th><th rowspan="2">KL chuẩn g/ĐVT</th><th colspan="{{ max(1,$shifts->count()) }}">Trọng lượng kg</th><th rowspan="2">Tổng KL kg</th><th rowspan="2">Giờ dừng</th><th rowspan="2">Mã nguyên nhân</th><th rowspan="2">Nguyên nhân dừng</th><th rowspan="2">Ghi chú</th></tr>
<tr>@forelse($shifts as $shift)<th>{{ $shift->name ?: $shift->code }}</th>@empty<th>Ca</th>@endforelse @forelse($shifts as $shift)<th>{{ $shift->name ?: $shift->code }}</th>@empty<th>Ca</th>@endforelse</tr>
@foreach($rows->groupBy(fn($row) => $row['date']->format('Y-m-d')) as $dateKey => $dateRows)
@foreach($dateRows as $row)
<tr><td>{{ $row['date']->format('d/m/Y') }}</td><td>{{ $row['machine_code'] }}</td><td>{{ $row['machine_name'] }}</td><td>{{ $row['product_code'] }}</td><td>{{ $row['product_name'] }}</td><td>{{ $row['unit'] }}</td>
@foreach($shifts as $shift)<td class="num">{{ $row['quantities'][$shift->id] ?? 0 }}</td>@endforeach
<td class="num">{{ $row['total_quantity'] }}</td><td class="num">{{ $row['good_quantity'] }}</td><td class="num">{{ $row['reject_quantity'] }}</td><td class="num">{{ $row['scrap_kg'] }}</td><td class="num">{{ $row['actual_length_m'] }}</td><td class="num">{{ $row['standard_weight_g'] }}</td>
@foreach($shifts as $shift)<td class="num">{{ $row['weights'][$shift->id] ?? 0 }}</td>@endforeach
<td class="num">{{ $row['total_weight_kg'] }}</td><td class="num">{{ $row['downtime_minutes']/60 }}</td><td>{{ implode(', ', $row['downtime_codes']) }}</td><td>{{ implode('; ', $row['downtime_reasons']) }}</td><td>{{ implode('; ', $row['notes']) }}</td></tr>
@endforeach
@php($total=$dailyTotals[$dateKey])
<tr class="total"><td colspan="6">Tổng ngày {{ $total['date']->format('d/m/Y') }}</td>@foreach($shifts as $shift)<td class="num">{{ $total['quantities'][$shift->id] ?? 0 }}</td>@endforeach<td class="num">{{ $total['total_quantity'] }}</td><td class="num">{{ $total['good_quantity'] }}</td><td class="num">{{ $total['reject_quantity'] }}</td><td class="num">{{ $total['scrap_kg'] }}</td><td colspan="2"></td>@foreach($shifts as $shift)<td class="num">{{ $total['weights'][$shift->id] ?? 0 }}</td>@endforeach<td class="num">{{ $total['total_weight_kg'] }}</td><td class="num">{{ $total['downtime_minutes']/60 }}</td><td colspan="3"></td></tr>
@endforeach
</table></body></html>
