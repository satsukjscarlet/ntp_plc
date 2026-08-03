<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_product_standards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('standard_rate', 15, 4);
            $table->string('rate_unit', 30)->default('kg/h');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['machine_id', 'product_id'], 'mps_machine_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_product_standards');
    }
};
