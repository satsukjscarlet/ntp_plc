<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            if (!Schema::hasColumn('plc_tags', 'display_group')) {
                $table->string('display_group', 100)
                    ->nullable()
                    ->after('display_name');
            }

            if (!Schema::hasColumn('plc_tags', 'display_order')) {
                $table->unsignedInteger('display_order')
                    ->default(0)
                    ->after('display_group');
            }

            if (!Schema::hasColumn('plc_tags', 'decimal_places')) {
                $table->unsignedTinyInteger('decimal_places')
                    ->default(1)
                    ->after('display_order');
            }

            if (!Schema::hasColumn('plc_tags', 'alarm_low')) {
                $table->decimal('alarm_low', 18, 6)
                    ->nullable()
                    ->after('history_deadband');
            }

            if (!Schema::hasColumn('plc_tags', 'alarm_high')) {
                $table->decimal('alarm_high', 18, 6)
                    ->nullable()
                    ->after('alarm_low');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            foreach ([
                'alarm_high',
                'alarm_low',
                'decimal_places',
                'display_order',
                'display_group',
            ] as $column) {
                if (Schema::hasColumn('plc_tags', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
