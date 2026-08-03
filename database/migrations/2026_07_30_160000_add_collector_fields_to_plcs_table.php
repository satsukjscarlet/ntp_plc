<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plcs', function (Blueprint $table) {
            $table->string('collector_token_hash', 64)
                ->nullable()
                ->unique()
                ->after('collector_url');

            $table->timestamp('last_seen_at')
                ->nullable()
                ->index()
                ->after('timezone');

            $table->string('connection_status', 20)
                ->default('UNKNOWN')
                ->index()
                ->after('last_seen_at');

            $table->text('last_error')
                ->nullable()
                ->after('connection_status');
        });
    }

    public function down(): void
    {
        Schema::table('plcs', function (Blueprint $table) {
            $table->dropUnique(['collector_token_hash']);
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['connection_status']);
            $table->dropColumn([
                'collector_token_hash',
                'last_seen_at',
                'connection_status',
                'last_error',
            ]);
        });
    }
};
