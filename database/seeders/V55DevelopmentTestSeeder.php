<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class V55DevelopmentTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductionShiftTestSeeder::class,
            ProductTestSeeder::class,
            MachineProductStandardTestSeeder::class,
            DowntimeReasonSeeder::class,
        ]);
    }
}
