@extends('layouts.app')

@section('title', 'Người dùng - NTP_PLC')
@section('page_title', 'Người dùng')
@section('page_subtitle', 'Quản lý tài khoản và vai trò')

@section('content')
<div class="d-flex justify-content-end mb-3">
    @can('users.create')
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Thêm người dùng
        </a>
    @endcan
</div>

<div class="card panel-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Tên đăng nhập</th>
                <th>Mã NV</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Đơn vị</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td><code>{{ $user->username }}</code></td>
                    <td>{{ $user->employee_code ?: '—' }}</td>
                    <td class="fw-semibold">{{ $user->name }}</td>
                    <td>{{ $user->email ?: '—' }}</td>
                    <td>
                        <div>{{ $user->department ?: '—' }}</div>
                        <small class="text-secondary">{{ $user->position }}</small>
                    </td>
                    <td>
                        @forelse ($user->roles as $role)
                            <span class="badge text-bg-primary">{{ $role->name }}</span>
                        @empty
                            <span class="text-secondary">Chưa gán</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $user->is_active ? 'Hoạt động' : 'Đã khóa' }}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        @can('users.update')
                            <a
                                href="{{ route('users.edit', $user) }}"
                                class="btn btn-sm btn-outline-primary"
                            >
                                Sửa
                            </a>
                        @endcan

                        @can('users.activate')
                            <form
                                class="d-inline"
                                method="POST"
                                action="{{ route('users.toggle-active', $user) }}"
                            >
                                @csrf
                                @method('PATCH')
                                <button
                                    class="btn btn-sm btn-outline-warning"
                                    onclick="return confirm('Xác nhận thay đổi trạng thái tài khoản?')"
                                >
                                    {{ $user->is_active ? 'Khóa' : 'Mở khóa' }}
                                </button>
                            </form>
                        @endcan

                        @can('users.delete')
                            <form
                                class="d-inline"
                                method="POST"
                                action="{{ route('users.destroy', $user) }}"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Bạn chắc chắn muốn xóa người dùng này?')"
                                >
                                    Xóa
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-secondary">
                        Chưa có người dùng.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
