<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('material_code', 100)->unique();
            $table->string('material_name');
            $table->string('unit', 30);
            $table->decimal('standard_weight_g', 15, 4)->nullable();
            $table->decimal('default_length_m', 15, 4)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('material_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
