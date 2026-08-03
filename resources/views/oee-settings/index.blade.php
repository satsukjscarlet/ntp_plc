@extends('layouts.app')

@section('title', 'Cấu hình OEE')
@section('page_title', 'Cấu hình OEE theo máy')
@section('page_subtitle', 'Mapping MD488 cho Availability và MD264 cho Performance')

@section('content')
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Máy</th>
                    <th>MD488 – Availability</th>
                    <th>Đơn vị</th>
                    <th>MD264 – Performance</th>
                    <th>Cách lấy</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($machines as $machine)
                    @php($setting = $machine->oeeSetting)
                    <tr>
                        <td>
                            <strong>{{ $machine->code }}</strong>
                            <div class="small text-muted">{{ $machine->name }}</div>
                        </td>
                        <td>
                            @if($setting?->availabilityTag)
                                {{ $setting->availabilityTag->display_name ?: $setting->availabilityTag->source_name }}
                                <div class="small text-muted">
                                    ID {{ $setting->availabilityTag->id }} · {{ $setting->availabilityTag->address }}
                                </div>
                            @else
                                <span class="text-danger">Chưa cấu hình</span>
                            @endif
                        </td>
                        <td>{{ match($setting?->availability_value_unit) {
                            'minutes' => 'Phút',
                            'seconds' => 'Giây',
                            default => 'Giờ',
                        } }}</td>
                        <td>
                            @if($setting?->performanceTag)
                                {{ $setting->performanceTag->display_name ?: $setting->performanceTag->source_name }}
                                <div class="small text-muted">
                                    ID {{ $setting->performanceTag->id }} · {{ $setting->performanceTag->address }}
                                </div>
                            @else
                                <span class="text-danger">Chưa cấu hình</span>
                            @endif
                        </td>
                        <td>
                            {{ match($setting?->performance_aggregation) {
                                'last' => 'Mẫu cuối',
                                'maximum' => 'Lớn nhất',
                                'average' => 'TB toàn bộ mẫu dương',
                                default => 'TB khoảng ổn định',
                            } }}
                            @if(($setting?->performance_aggregation ?? 'stable_average') === 'stable_average')
                                <div class="small text-muted">
                                    ±{{ number_format((float) ($setting?->performance_stability_tolerance ?? 1), 2) }} kg/h ·
                                    ≥{{ (int) ($setting?->performance_stability_min_seconds ?? 60) }} giây
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $setting?->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $setting?->is_active ? 'Đang dùng' : 'Chưa kích hoạt' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('machines.oee-settings.edit', $machine) }}"
                               class="btn btn-sm btn-primary">
                                Cấu hình
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Chưa có máy.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
