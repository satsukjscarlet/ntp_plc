<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_production_declaration_items', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('shift_production_declaration_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('machine_product_standard_id')->nullable();

            $table->string('product_code_snapshot', 100);
            $table->string('product_name_snapshot', 255);
            $table->string('unit_snapshot', 50)->nullable();
            $table->decimal('standard_weight_g_snapshot', 20, 4)->nullable();
            $table->decimal('default_length_m_snapshot', 20, 4)->nullable();
            $table->decimal('actual_length_m', 20, 4)->nullable();
            $table->decimal('standard_rate_snapshot', 20, 4)->nullable();
            $table->string('rate_unit_snapshot', 30)->nullable();

            $table->decimal('total_quantity', 20, 3)->default(0);
            $table->decimal('good_quantity', 20, 3)->default(0);
            $table->decimal('reject_quantity', 20, 3)->default(0);
            $table->decimal('scrap_quantity', 20, 3)->default(0);
            $table->decimal('converted_total_kg', 20, 3)->default(0);
            $table->decimal('converted_good_kg', 20, 3)->default(0);
            $table->decimal('converted_reject_kg', 20, 3)->default(0);
            $table->decimal('converted_scrap_kg', 20, 3)->default(0);

            $table->string('work_order', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            // Đặt tên khóa ngoại ngắn để không vượt giới hạn 64 ký tự của MySQL.
            $table->foreign(
                'shift_production_declaration_id',
                'fk_spdi_declaration'
            )
                ->references('id')
                ->on('shift_production_declarations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('product_id', 'fk_spdi_product')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            $table->foreign(
                'machine_product_standard_id',
                'fk_spdi_standard'
            )
                ->references('id')
                ->on('machine_product_standards')
                ->nullOnDelete();

            $table->index(
                ['shift_production_declaration_id', 'display_order'],
                'idx_spdi_declaration_order'
            );
            $table->index(['product_id', 'created_at'], 'idx_spdi_product_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_production_declaration_items');
    }
};
