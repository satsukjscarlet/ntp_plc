<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_oee_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('machine_oee_settings', 'performance_stability_tolerance')) {
                $table->decimal('performance_stability_tolerance', 10, 4)
                    ->default(1.0000)
                    ->after('performance_aggregation');
            }

            if (!Schema::hasColumn('machine_oee_settings', 'performance_stability_min_seconds')) {
                $table->unsignedInteger('performance_stability_min_seconds')
                    ->default(60)
                    ->after('performance_stability_tolerance');
            }

            if (!Schema::hasColumn('machine_oee_settings', 'performance_min_valid_value')) {
                $table->decimal('performance_min_valid_value', 12, 4)
                    ->default(0.0000)
                    ->after('performance_stability_min_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machine_oee_settings', function (Blueprint $table): void {
            foreach ([
                'performance_stability_tolerance',
                'performance_stability_min_seconds',
                'performance_min_valid_value',
            ] as $column) {
                if (Schema::hasColumn('machine_oee_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
