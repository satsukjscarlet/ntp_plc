<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            if (!Schema::hasColumn('plc_tags', 'display_format')) {
                $table->string('display_format', 100)
                    ->nullable()
                    ->after('data_type');
            }

            if (!Schema::hasColumn('plc_tags', 'comment')) {
                $table->text('comment')
                    ->nullable();
            }

            if (!Schema::hasColumn('plc_tags', 'last_synced_at')) {
                $table->timestamp('last_synced_at')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            $columns = [];

            foreach ([
                'display_format',
                'comment',
                'last_synced_at',
            ] as $column) {
                if (Schema::hasColumn('plc_tags', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
