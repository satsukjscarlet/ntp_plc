<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shift_production_declarations',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('machine_id')
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
                $table->foreignId('production_shift_id')
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
                $table->date('production_date');
                $table->dateTime('shift_started_at');
                $table->dateTime('shift_ended_at');
                $table->string('product_code', 100)->nullable();
                $table->string('product_name', 255)->nullable();
                $table->string('work_order', 100)->nullable();

                $table->decimal('good_quantity', 20, 3)->default(0);
                $table->decimal('reject_quantity', 20, 3)->default(0);
                $table->decimal('total_quantity', 20, 3)->default(0);

                $table->decimal('planned_downtime_minutes', 12, 2)->default(0);
                $table->decimal('planned_production_minutes', 12, 2)->nullable();
                $table->decimal('plc_runtime_minutes', 12, 2)->nullable();
                $table->decimal('unplanned_downtime_minutes', 12, 2)->nullable();
                $table->decimal('ideal_rate_per_minute', 20, 6)->nullable();

                $table->decimal('availability', 8, 4)->nullable();
                $table->decimal('performance', 8, 4)->nullable();
                $table->decimal('quality_rate', 8, 4)->nullable();
                $table->decimal('oee', 8, 4)->nullable();

                $table->string('status', 20)->default('draft')->index();
                $table->foreignId('operator_id')
                    ->constrained('users')
                    ->restrictOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('note')->nullable();
                $table->text('calculation_note')->nullable();
                $table->timestamps();

                $table->unique(
                    ['machine_id', 'production_shift_id', 'production_date'],
                    'shift_declaration_unique'
                );
                $table->index(['production_date', 'status']);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_production_declarations');
    }
};
