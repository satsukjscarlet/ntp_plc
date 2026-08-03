<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plc_collection_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plc_id')
                ->constrained('plcs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('status', 20)->index();
            $table->unsignedInteger('total_tags')->default(0);
            $table->unsignedInteger('current_values')->default(0);
            $table->unsignedInteger('history_values')->default(0);
            $table->unsignedInteger('unknown_tags')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('collector_name', 150)->nullable();
            $table->string('collector_version', 50)->nullable();
            $table->string('request_id', 100)->nullable()->index();

            $table->text('message')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['plc_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plc_collection_logs');
    }
};
