<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plc_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plc_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('address', 100)->nullable();
            $table->string('source_name', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('data_type', 30)->default('STRING');
            $table->string('unit', 50)->nullable();
            $table->string('display_group', 100)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('oee_role', 50)->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['plc_id', 'source_name']);
            $table->index(['plc_id', 'display_group', 'display_order']);
            $table->index(['plc_id', 'oee_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plc_tags');
    }
};
