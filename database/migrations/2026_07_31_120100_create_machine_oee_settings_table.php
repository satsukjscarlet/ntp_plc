<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_oee_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('machine_id')
                ->unique()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('run_status_tag_id')
                ->nullable()
                ->constrained('plc_tags')
                ->nullOnDelete();
            $table->string('run_value_mode', 20)->default('greater_than');
            $table->decimal('run_value_threshold', 20, 6)->default(0);
            $table->decimal('ideal_rate_per_minute', 20, 6)->nullable();
            $table->unsignedInteger('collector_gap_limit_seconds')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_oee_settings');
    }
};
