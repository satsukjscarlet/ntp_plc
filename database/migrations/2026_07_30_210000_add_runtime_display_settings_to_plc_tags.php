<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            if (!Schema::hasColumn('plc_tags', 'display_unit')) {
                $table->string('display_unit', 30)->nullable()
                    ->after('monitoring_column_id');
            }

            if (!Schema::hasColumn('plc_tags', 'display_decimals')) {
                $table->unsignedTinyInteger('display_decimals')->default(1)
                    ->after('display_unit');
            }

            if (!Schema::hasColumn('plc_tags', 'warning_low')) {
                $table->decimal('warning_low', 18, 6)->nullable()
                    ->after('display_decimals');
            }

            if (!Schema::hasColumn('plc_tags', 'warning_high')) {
                $table->decimal('warning_high', 18, 6)->nullable()
                    ->after('warning_low');
            }

            if (!Schema::hasColumn('plc_tags', 'alarm_enabled')) {
                $table->boolean('alarm_enabled')->default(false)
                    ->after('warning_high');
            }

            if (!Schema::hasColumn('plc_tags', 'blink_on_alarm')) {
                $table->boolean('blink_on_alarm')->default(false)
                    ->after('alarm_enabled');
            }

            if (!Schema::hasColumn('plc_tags', 'chart_enabled')) {
                $table->boolean('chart_enabled')->default(false)
                    ->after('blink_on_alarm');
            }

            if (!Schema::hasColumn('plc_tags', 'display_color')) {
                $table->string('display_color', 30)->nullable()
                    ->after('chart_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            $columns = [
                'display_unit',
                'display_decimals',
                'warning_low',
                'warning_high',
                'alarm_enabled',
                'blink_on_alarm',
                'chart_enabled',
                'display_color',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('plc_tags', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
