@extends('layouts.app')

@section('title', 'Vai trò - NTP_PLC')
@section('page_title', 'Vai trò và quyền')
@section('page_subtitle', 'Phân quyền truy cập hệ thống')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('roles.create')
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm vai trò
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Vai trò</th>
                <th>Số quyền</th>
                <th>Người dùng</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td class="fw-semibold">{{ $role->name }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td class="text-end">
                        @can('roles.update')
                            <a
                                href="{{ route('roles.edit', $role) }}"
                                class="btn btn-sm btn-outline-primary"
                            >
                                Sửa
                            </a>
                        @endcan

                        @can('roles.delete')
                            <form
                                class="d-inline"
                                method="POST"
                                action="{{ route('roles.destroy', $role) }}"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Xóa vai trò này?')"
                                >
                                    Xóa
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ($roles->hasPages())
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
    @endif
</div>
@endsection
