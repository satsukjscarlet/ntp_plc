<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name', 150);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('ip_address', 45);
            $table->unsignedSmallInteger('port')->default(80);
            $table->string('protocol', 50)->default('HTTP_PORTAL');
            $table->text('collector_url')->nullable();
            $table->unsignedInteger('scan_interval')->default(5)
                ->comment('Chu kỳ quét, đơn vị giây');
            $table->string('timezone', 64)->default('Asia/Ho_Chi_Minh');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['machine_id', 'ip_address', 'port']);
            $table->index(['machine_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plcs');
    }
};
