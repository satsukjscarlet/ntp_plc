<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_layout_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('machine_id')
                ->constrained('machines')
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name_vi', 150);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['machine_id', 'code']);
        });

        Schema::create('monitoring_layout_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('monitoring_layout_sections')
                ->cascadeOnDelete();

            $table->string('code', 80);
            $table->string('name_vi', 150);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section_id', 'code']);
        });

        Schema::create('monitoring_layout_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('monitoring_layout_sections')
                ->cascadeOnDelete();

            $table->string('code', 80);
            $table->string('name_vi', 150);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_layout_columns');
        Schema::dropIfExists('monitoring_layout_rows');
        Schema::dropIfExists('monitoring_layout_sections');
    }
};
