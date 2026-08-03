@extends('layouts.app')

@section('title', 'Máy - NTP_PLC')
@section('page_title', 'Máy')
@section('page_subtitle', 'Quản lý danh mục thiết bị')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('machines.create')
        <a href="{{ route('machines.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm mới
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Mã</th>
                <th>Tên máy</th>
                <th>Nhà máy / Dây chuyền</th>
                <th>Hãng / Model</th>
                <th>PLC</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($machines as $machine)
                <tr>
                    <td>{{ $machine->code }}</td>
                    <td>{{ $machine->name }}</td>
                    <td>{{ $machine->productionLine->plant->name }} / {{ $machine->productionLine->name }}</td>
                    <td>{{ $machine->manufacturer ?: '—' }} / {{ $machine->model ?: '—' }}</td>
                    <td>{{ $machine->plcs_count }}</td>
                    <td><span class="badge {{ $machine->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $machine->is_active ? 'Hoạt động' : 'Ngừng' }}</span></td>
                    <td class="text-end text-nowrap">
                        @can('machines.update')
                            <a href="{{ route('machines.edit', $machine) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @endcan
                        @can('machines.delete')
                            <form class="d-inline" method="POST" action="{{ route('machines.destroy', $machine) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-5 text-secondary">Chưa có dữ liệu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($machines->hasPages())
        <div class="card-footer">{{ $machines->links() }}</div>
    @endif
</div>
@endsection
