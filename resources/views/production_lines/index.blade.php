@extends('layouts.app')

@section('title', 'Dây chuyền - NTP_PLC')
@section('page_title', 'Dây chuyền')
@section('page_subtitle', 'Quản lý danh mục thiết bị')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('production-lines.create')
        <a href="{{ route('production-lines.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm mới
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Nhà máy</th>
                <th>Mã</th>
                <th>Tên dây chuyền</th>
                <th>Số máy</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($productionLines as $productionLine)
                <tr>
                    <td>{{ $productionLine->plant->name }}</td>
                    <td>{{ $productionLine->code }}</td>
                    <td>{{ $productionLine->name }}</td>
                    <td>{{ $productionLine->machines_count }}</td>
                    <td><span class="badge {{ $productionLine->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $productionLine->is_active ? 'Hoạt động' : 'Ngừng' }}</span></td>
                    <td class="text-end text-nowrap">
                        @can('production-lines.update')
                            <a href="{{ route('production-lines.edit', $productionLine) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @endcan
                        @can('production-lines.delete')
                            <form class="d-inline" method="POST" action="{{ route('production-lines.destroy', $productionLine) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-5 text-secondary">Chưa có dữ liệu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($productionLines->hasPages())
        <div class="card-footer">{{ $productionLines->links() }}</div>
    @endif
</div>
@endsection
