<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plc_current_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plc_tag_id')
                ->unique()
                ->constrained('plc_tags')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('raw_value')->nullable();
            $table->decimal('numeric_value', 20, 6)->nullable();
            $table->text('text_value')->nullable();

            $table->string('quality', 20)
                ->default('GOOD')
                ->index();

            $table->timestamp('collected_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plc_current_values');
    }
};
