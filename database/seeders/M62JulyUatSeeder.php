<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class M62JulyUatSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            NtpPlcTestUserSeeder::class,
            M62JulyProductionTestSeeder::class,
        ]);
    }
}
