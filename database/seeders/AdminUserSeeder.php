<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            [
                'email' => 'thientuanntp@gmail.com',
            ],
            [
                'employee_code' => 'ADMIN',
                'name' => 'Quản trị hệ thống',
                'department' => 'Ban CNTT và Chuyển đổi số',
                'position' => 'Quản trị viên',
                'password' => Hash::make('123123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['SUPER_ADMIN']);
    }
}