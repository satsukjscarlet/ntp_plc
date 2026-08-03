<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::query()
            ->orderBy('name')
            ->pluck('name', 'name');

        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->create(
                $request->safe()->except('roles')
            );

            $user->syncRoles($request->validated('roles', []));
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Đã tạo người dùng thành công.');
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        $roles = Role::query()
            ->orderBy('name')
            ->pluck('name', 'name');

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        DB::transaction(function () use ($request, $user): void {
            $data = $request->safe()->except('roles');

            if (empty($data['password'] ?? null)) {
                unset($data['password']);
            }

            $user->update($data);
            $user->syncRoles($request->validated('roles', []));
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Đã cập nhật người dùng.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if(
            auth()->id() === $user->id,
            422,
            'Không thể xóa tài khoản đang đăng nhập.'
        );

        abort_if(
            $user->hasRole('SUPER_ADMIN'),
            422,
            'Không thể xóa tài khoản SUPER_ADMIN.'
        );

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Đã xóa người dùng.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if(
            auth()->id() === $user->id,
            422,
            'Không thể khóa tài khoản đang đăng nhập.'
        );

        abort_if(
            $user->hasRole('SUPER_ADMIN'),
            422,
            'Không thể khóa tài khoản SUPER_ADMIN.'
        );

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? 'Đã kích hoạt tài khoản.'
                : 'Đã khóa tài khoản.'
        );
    }
}
