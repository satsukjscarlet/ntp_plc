<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_line_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('model', 100)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->date('installation_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['production_line_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
