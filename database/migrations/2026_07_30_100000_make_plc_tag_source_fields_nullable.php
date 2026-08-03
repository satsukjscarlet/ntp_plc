<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plc_tags', function (Blueprint $table): void {
            $table->string('address', 191)->nullable()->change();
            $table->string('data_type', 50)->nullable()->change();
            $table->string('unit', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Không hoàn nguyên về NOT NULL vì dữ liệu PLC thực tế có thể thiếu
        // Address, Data Type hoặc Unit.
    }
};
