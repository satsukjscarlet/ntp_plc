@php
    $selectedPermissions = old(
        'permissions',
        $selectedPermissions ?? []
    );
@endphp

<div class="mb-4">
    <label class="form-label">Tên vai trò <span class="text-danger">*</span></label>
    <input
        class="form-control"
        name="name"
        required
        value="{{ old('name', $role->name ?? '') }}"
        {{ isset($role) && $role->name === 'SUPER_ADMIN' ? 'readonly' : '' }}
    >
</div>

<div class="row g-3">
    @foreach ($permissionGroups as $group => $permissions)
        <div class="col-12 col-lg-6">
            <div class="permission-group">
                <div class="permission-group-title">
                    {{ strtoupper($group) }}
                </div>

                @foreach ($permissions as $permission)
                    <label class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission['name'] }}"
                            @checked(in_array($permission['name'], $selectedPermissions, true))
                            {{ isset($role) && $role->name === 'SUPER_ADMIN' ? 'disabled' : '' }}
                        >
                        <span class="form-check-label">
                            {{ $permission['name'] }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
