<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_current_values', function (Blueprint $table): void {
            if (!Schema::hasColumn('plc_current_values', 'history_base_value')) {
                $table->text('history_base_value')
                    ->nullable()
                    ->after('raw_value');
            }

            if (!Schema::hasColumn('plc_current_values', 'history_base_quality')) {
                $table->string('history_base_quality', 30)
                    ->nullable()
                    ->after('history_base_value');
            }

            if (!Schema::hasColumn('plc_current_values', 'last_history_at')) {
                $table->timestamp('last_history_at')
                    ->nullable()
                    ->after('history_base_quality');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plc_current_values', function (Blueprint $table): void {
            if (Schema::hasColumn('plc_current_values', 'last_history_at')) {
                $table->dropColumn('last_history_at');
            }

            if (Schema::hasColumn('plc_current_values', 'history_base_quality')) {
                $table->dropColumn('history_base_quality');
            }

            if (Schema::hasColumn('plc_current_values', 'history_base_value')) {
                $table->dropColumn('history_base_value');
            }
        });
    }
};
