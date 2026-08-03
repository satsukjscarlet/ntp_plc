<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->paginate(20);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('roles.create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $role = Role::query()->create([
                'name' => $request->validated('name'),
                'guard_name' => 'web',
            ]);

            $role->syncPermissions(
                $request->validated('permissions', [])
            );
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Đã tạo vai trò.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $role
                ->permissions
                ->pluck('name')
                ->all(),
        ]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role
    ): RedirectResponse {
        abort_if(
            $role->name === 'SUPER_ADMIN',
            422,
            'Không chỉnh sửa vai trò SUPER_ADMIN.'
        );

        DB::transaction(function () use ($request, $role): void {
            $role->update([
                'name' => $request->validated('name'),
            ]);

            $role->syncPermissions(
                $request->validated('permissions', [])
            );
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'Đã cập nhật vai trò.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(
            $role->name === 'SUPER_ADMIN',
            422,
            'Không thể xóa vai trò SUPER_ADMIN.'
        );

        abort_if(
            $role->users()->exists(),
            422,
            'Vai trò đang được gán cho người dùng.'
        );

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Đã xóa vai trò.');
    }

    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) =>
                str($permission->name)->before('.')->toString()
            )
            ->toArray();
    }
}
