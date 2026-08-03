<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 50)
                ->nullable()
                ->unique()
                ->after('id');

            $table->string('department', 150)
                ->nullable()
                ->after('email');

            $table->string('position', 150)
                ->nullable()
                ->after('department');

            $table->boolean('is_active')
                ->default(true)
                ->after('position');

            $table->index('is_active');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['department']);

            $table->dropColumn([
                'employee_code',
                'department',
                'position',
                'is_active',
            ]);
        });
    }
};