<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            $table->foreignId('monitoring_section_id')
                ->nullable()
                ->after('display_group')
                ->constrained('monitoring_layout_sections')
                ->nullOnDelete();

            $table->foreignId('monitoring_row_id')
                ->nullable()
                ->after('monitoring_section_id')
                ->constrained('monitoring_layout_rows')
                ->nullOnDelete();

            $table->foreignId('monitoring_column_id')
                ->nullable()
                ->after('monitoring_row_id')
                ->constrained('monitoring_layout_columns')
                ->nullOnDelete();

            $table->index([
                'plc_id',
                'monitoring_section_id',
                'monitoring_row_id',
                'monitoring_column_id',
            ], 'plc_tags_monitoring_position_idx');
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            $table->dropIndex('plc_tags_monitoring_position_idx');

            $table->dropConstrainedForeignId('monitoring_column_id');
            $table->dropConstrainedForeignId('monitoring_row_id');
            $table->dropConstrainedForeignId('monitoring_section_id');
        });
    }
};
