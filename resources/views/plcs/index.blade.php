@extends('layouts.app')

@section('title', 'PLC - NTP_PLC')
@section('page_title', 'PLC')
@section('page_subtitle', 'Quản lý danh mục thiết bị')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('plcs.create')
        <a href="{{ route('plcs.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm mới
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Máy</th>
                <th>Tên PLC</th>
                <th>IP:Port</th>
                <th>Giao thức</th>
                <th>Chu kỳ</th>
                <th>Tag</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($plcs as $plc)
                <tr>
                    <td>{{ $plc->machine->code }} - {{ $plc->machine->name }}</td>
                    <td>{{ $plc->name }}</td>
                    <td>{{ $plc->ip_address }}:{{ $plc->port }}</td>
                    <td>{{ $plc->protocol }}</td>
                    <td>{{ $plc->scan_interval }} giây</td>
                    <td>{{ $plc->tags_count }}</td>
                    <td><span class="badge {{ $plc->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $plc->is_active ? 'Hoạt động' : 'Ngừng' }}</span></td>
                    <td class="text-end text-nowrap">
                        @can('plcs.update')
                            <a href="{{ route('plcs.edit', $plc) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @endcan
                        @can('plcs.delete')
                            <form class="d-inline" method="POST" action="{{ route('plcs.destroy', $plc) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-5 text-secondary">Chưa có dữ liệu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($plcs->hasPages())
        <div class="card-footer">{{ $plcs->links() }}</div>
    @endif
</div>
@endsection
