@extends('layouts.app')

@section('title', 'Nhà máy - NTP_PLC')
@section('page_title', 'Nhà máy')
@section('page_subtitle', 'Quản lý danh mục thiết bị')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('plants.create')
        <a href="{{ route('plants.create') }}" class="btn btn-primary">
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
                <th>Tên nhà máy</th>
                <th>Địa chỉ</th>
                <th>Dây chuyền</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($plants as $plant)
                <tr>
                    <td>{{ $plant->code }}</td>
                    <td>{{ $plant->name }}</td>
                    <td>{{ $plant->address ?: '—' }}</td>
                    <td>{{ $plant->production_lines_count }}</td>
                    <td><span class="badge {{ $plant->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $plant->is_active ? 'Hoạt động' : 'Ngừng' }}</span></td>
                    <td class="text-end text-nowrap">
                        @can('plants.update')
                            <a href="{{ route('plants.edit', $plant) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @endcan
                        @can('plants.delete')
                            <form class="d-inline" method="POST" action="{{ route('plants.destroy', $plant) }}">
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
    @if ($plants->hasPages())
        <div class="card-footer">{{ $plants->links() }}</div>
    @endif
</div>
@endsection
