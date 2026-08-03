@extends('layouts.app')

@section('title', 'Báo cáo OEE')
@section('page_title', 'Báo cáo OEE theo ca và ngày')
@section('page_subtitle', 'OEE ngày là trung bình các ca đã có kết quả chính thức')

@section('content')
<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Máy</label>
            <select name="machine_id" class="form-select">
                <option value="">Tất cả máy</option>
                @foreach($machines as $machine)
                    <option value="{{ $machine->id }}" @selected((string)request('machine_id') === (string)$machine->id)>
                        {{ $machine->code }} - {{ $machine->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Từ ngày</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Đến ngày</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc</button></div>
    </div>
</form>

<div class="card shadow-sm mb-3">
    <div class="card-header fw-semibold">OEE theo ngày</div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light text-center"><tr><th>Ngày</th><th>Máy</th><th>Số ca</th><th>A</th><th>P</th><th>Q</th><th>OEE ngày</th></tr></thead>
            <tbody>
            @forelse($daily as $row)
                <tr>
                    <td>{{ $row['date']->format('d/m/Y') }}</td>
                    <td><strong>{{ $row['machine']->code }}</strong> - {{ $row['machine']->name }}</td>
                    <td class="text-center">{{ $row['shift_count'] }}</td>
                    <td class="text-end">{{ number_format($row['availability'] * 100, 2) }}%</td>
                    <td class="text-end">{{ number_format($row['performance'] * 100, 2) }}%</td>
                    <td class="text-end">{{ number_format($row['quality'] * 100, 2) }}%</td>
                    <td class="text-end fw-bold">{{ number_format($row['oee'] * 100, 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Chưa có dữ liệu OEE.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header fw-semibold">Chi tiết theo ca</div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="table-light text-center"><tr><th>Ngày</th><th>Máy</th><th>Ca</th><th>A</th><th>P</th><th>Q</th><th>OEE</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->production_date->format('d/m/Y') }}</td>
                    <td>{{ $row->machine->code }}</td>
                    <td>{{ $row->shift->name }}</td>
                    <td class="text-end">{{ number_format((float)$row->availability * 100, 2) }}%</td>
                    <td class="text-end">{{ number_format((float)$row->performance * 100, 2) }}%</td>
                    <td class="text-end">{{ number_format((float)$row->quality_rate * 100, 2) }}%</td>
                    <td class="text-end fw-bold">{{ number_format((float)$row->oee * 100, 2) }}%</td>
                    <td class="text-center"><a class="btn btn-sm btn-outline-primary" href="{{ route('production-declarations.show', $row) }}">Chi tiết</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Chưa có dữ liệu OEE.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
