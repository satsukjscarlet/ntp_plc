<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            if (!Schema::hasColumn('plc_tags', 'history_enabled')) {
                $table->boolean('history_enabled')
                    ->default(true)
                    ->after('is_visible');
            }

            if (!Schema::hasColumn('plc_tags', 'history_deadband')) {
                $table->decimal('history_deadband', 18, 6)
                    ->default(0)
                    ->after('history_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            if (Schema::hasColumn('plc_tags', 'history_deadband')) {
                $table->dropColumn('history_deadband');
            }

            if (Schema::hasColumn('plc_tags', 'history_enabled')) {
                $table->dropColumn('history_enabled');
            }
        });
    }
};
