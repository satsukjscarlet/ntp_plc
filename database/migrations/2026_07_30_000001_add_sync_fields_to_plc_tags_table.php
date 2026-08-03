<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table) {
            if (!Schema::hasColumn('plc_tags', 'portal_name')) {
                $table->string('portal_name')->nullable()->after('source_name');
            }

            if (!Schema::hasColumn('plc_tags', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('plc_tags', 'sync_status')) {
                $table->string('sync_status', 20)
                    ->default('MATCHED')
                    ->after('last_seen_at');
            }

            if (!Schema::hasColumn('plc_tags', 'source_key')) {
                $table->string('source_key', 191)
                    ->nullable()
                    ->after('sync_status');
            }
        });

        Schema::table('plc_tags', function (Blueprint $table) {
            $table->index(['plc_id', 'source_key'], 'plc_tags_plc_source_key_idx');
            $table->index(['plc_id', 'sync_status'], 'plc_tags_plc_sync_status_idx');
            $table->index(['plc_id', 'last_seen_at'], 'plc_tags_plc_last_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table) {
            $table->dropIndex('plc_tags_plc_source_key_idx');
            $table->dropIndex('plc_tags_plc_sync_status_idx');
            $table->dropIndex('plc_tags_plc_last_seen_idx');

            $table->dropColumn([
                'portal_name',
                'last_seen_at',
                'sync_status',
                'source_key',
            ]);
        });
    }
};
