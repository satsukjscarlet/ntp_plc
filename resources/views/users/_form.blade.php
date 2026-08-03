@php
    $selectedRoles = old(
        'roles',
        isset($user) ? $user->roles->pluck('name')->all() : []
    );
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">
            Tên đăng nhập <span class="text-danger">*</span>
        </label>
        <input
            class="form-control @error('username') is-invalid @enderror"
            name="username"
            required
            autocomplete="off"
            placeholder="Ví dụ: vanhanh.m62"
            value="{{ old('username', $user->username ?? '') }}"
        >
        <div class="form-text">
            Chữ thường không dấu, số, dấu chấm, gạch ngang hoặc gạch dưới.
        </div>
        @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Mã nhân viên</label>
        <input
            class="form-control @error('employee_code') is-invalid @enderror"
            name="employee_code"
            value="{{ old('employee_code', $user->employee_code ?? '') }}"
        >
        @error('employee_code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">
            Họ và tên <span class="text-danger">*</span>
        </label>
        <input
            class="form-control @error('name') is-invalid @enderror"
            name="name"
            required
            value="{{ old('name', $user->name ?? '') }}"
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Email <span class="text-muted">(không bắt buộc)</span>
        </label>
        <input
            class="form-control @error('email') is-invalid @enderror"
            type="email"
            name="email"
            value="{{ old('email', $user->email ?? '') }}"
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Phòng ban</label>
        <input
            class="form-control"
            name="department"
            value="{{ old('department', $user->department ?? '') }}"
        >
    </div>

    <div class="col-md-3">
        <label class="form-label">Chức vụ</label>
        <input
            class="form-control"
            name="position"
            value="{{ old('position', $user->position ?? '') }}"
        >
    </div>

    <div class="col-md-3">
        <label class="form-label">Trạng thái</label>
        <select class="form-select" name="is_active">
            <option value="1" @selected(old('is_active', $user->is_active ?? true) == 1)>
                Hoạt động
            </option>
            <option value="0" @selected(old('is_active', $user->is_active ?? true) == 0)>
                Khóa
            </option>
        </select>
    </div>

    <div class="col-md-9">
        <label class="form-label">Vai trò</label>
        <select class="form-select" name="roles[]" multiple size="4">
            @foreach ($roles as $role)
                <option
                    value="{{ $role }}"
                    @selected(in_array($role, $selectedRoles, true))
                >
                    {{ $role }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Mật khẩu {{ isset($user) ? '(để trống nếu không đổi)' : '' }}
        </label>
        <input
            class="form-control @error('password') is-invalid @enderror"
            type="password"
            name="password"
            autocomplete="new-password"
            {{ isset($user) ? '' : 'required' }}
        >
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Xác nhận mật khẩu</label>
        <input
            class="form-control"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            {{ isset($user) ? '' : 'required' }}
        >
    </div>
</div>
