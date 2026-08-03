<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_oee_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('machine_oee_settings', 'availability_tag_id')) {
                $table->unsignedBigInteger('availability_tag_id')
                    ->nullable()
                    ->after('machine_id');

                $table->foreign(
                    'availability_tag_id',
                    'fk_machine_oee_availability_tag'
                )->references('id')
                    ->on('plc_tags')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('machine_oee_settings', 'availability_value_unit')) {
                $table->string('availability_value_unit', 20)
                    ->default('hours')
                    ->after('availability_tag_id');
            }

            if (!Schema::hasColumn('machine_oee_settings', 'performance_tag_id')) {
                $table->unsignedBigInteger('performance_tag_id')
                    ->nullable()
                    ->after('availability_value_unit');

                $table->foreign(
                    'performance_tag_id',
                    'fk_machine_oee_performance_tag'
                )->references('id')
                    ->on('plc_tags')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('machine_oee_settings', 'performance_aggregation')) {
                $table->string('performance_aggregation', 20)
                    ->default('average')
                    ->after('performance_tag_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machine_oee_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('machine_oee_settings', 'availability_tag_id')) {
                $table->dropForeign('fk_machine_oee_availability_tag');
            }

            if (Schema::hasColumn('machine_oee_settings', 'performance_tag_id')) {
                $table->dropForeign('fk_machine_oee_performance_tag');
            }

            foreach ([
                'availability_tag_id',
                'availability_value_unit',
                'performance_tag_id',
                'performance_aggregation',
            ] as $column) {
                if (Schema::hasColumn('machine_oee_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
