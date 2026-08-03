<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class NtpPlcTestUserSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'Ntp@123456';

    public function run(): void
    {
        if (
            app()->environment('production')
            && !filter_var(
                env('NTP_ALLOW_TEST_SEEDERS', false),
                FILTER_VALIDATE_BOOL
            )
        ) {
            throw new RuntimeException(
                'Không được chạy TestUserSeeder trên production.'
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
         * Dùng prefix test_ để không đụng username thật đang có,
         * ví dụ project hiện đã có username "admin" ở user ID 1.
         */
        $accounts = [
            ['username' => 'test_superadmin', 'employee_code' => 'TEST-SA', 'name' => 'Test Super Admin', 'department' => 'CNTT', 'position' => 'Super Admin kiểm thử', 'role' => 'SUPER_ADMIN'],
            ['username' => 'test_admin', 'employee_code' => 'TEST-ADMIN', 'name' => 'Test Admin', 'department' => 'CNTT', 'position' => 'Admin kiểm thử', 'role' => 'ADMIN'],
            ['username' => 'test_it', 'employee_code' => 'TEST-IT', 'name' => 'Test Ban CNTT', 'department' => 'Ban CNTT', 'position' => 'Chuyên viên CNTT kiểm thử', 'role' => 'BAN_CNTT'],
            ['username' => 'test_quandoc', 'employee_code' => 'TEST-QD', 'name' => 'Test Quản đốc', 'department' => 'Sản xuất', 'position' => 'Quản đốc kiểm thử', 'role' => 'QUAN_DOC'],
            ['username' => 'test_totruong', 'employee_code' => 'TEST-TT', 'name' => 'Test Tổ trưởng', 'department' => 'Sản xuất', 'position' => 'Tổ trưởng kiểm thử', 'role' => 'TO_TRUONG'],
            ['username' => 'test_kcs', 'employee_code' => 'TEST-KCS', 'name' => 'Test KCS', 'department' => 'KCS', 'position' => 'KCS kiểm thử', 'role' => 'KCS'],
            ['username' => 'test_vanhanh', 'employee_code' => 'TEST-VH', 'name' => 'Test Vận hành M62', 'department' => 'Sản xuất', 'position' => 'Nhân viên vận hành M62', 'role' => 'VAN_HANH'],
            ['username' => 'test_baocao', 'employee_code' => 'TEST-BC', 'name' => 'Test Xem báo cáo', 'department' => 'Ban lãnh đạo', 'position' => 'Người xem báo cáo', 'role' => 'XEM_BAO_CAO'],
        ];

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                $this->assertRoleExists($account['role']);

                /*
                 * Tài khoản test cũ được nhận diện bằng employee_code TEST-*.
                 * Seeder chỉ cập nhật đúng user test đó, không đụng user thật
                 * dù user thật có username admin, it, kcs...
                 */
                $user = User::query()
                    ->where('employee_code', $account['employee_code'])
                    ->first();

                if (!$user) {
                    $user = User::query()
                        ->where('username', $account['username'])
                        ->first();
                }

                $user ??= new User();

                $usernameOwner = User::query()
                    ->where('username', $account['username'])
                    ->when(
                        $user->exists,
                        fn ($query) => $query->whereKeyNot($user->id)
                    )
                    ->first();

                if ($usernameOwner) {
                    throw new RuntimeException(sprintf(
                        'Username %s đã thuộc user ID %d. '
                        . 'Hãy đổi username test hoặc kiểm tra bảng users.',
                        $account['username'],
                        $usernameOwner->id
                    ));
                }

                $user->fill([
                    'username' => $account['username'],
                    'employee_code' => $account['employee_code'],
                    'name' => $account['name'],
                    'email' => null,
                    'department' => $account['department'],
                    'position' => $account['position'],
                    'is_active' => true,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                ]);

                $user->save();
                $user->syncRoles([$account['role']]);

                $this->command?->line(sprintf(
                    '[%s] %s / %s — user ID %d',
                    $account['role'],
                    $account['username'],
                    self::DEFAULT_PASSWORD,
                    $user->id
                ));
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            'Đã tạo/cập nhật 8 tài khoản test với prefix test_, không đụng tài khoản thật.'
        );
    }

    private function assertRoleExists(string $role): void
    {
        if (!Role::query()
            ->where('name', $role)
            ->where('guard_name', 'web')
            ->exists()) {
            throw new RuntimeException(
                "Chưa có role {$role}. Hãy chạy RolePermissionSeeder trước."
            );
        }
    }
}
