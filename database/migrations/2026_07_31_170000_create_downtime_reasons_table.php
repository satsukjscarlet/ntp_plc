<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đồng bộ bảng downtime_reasons với schema V5.5.
     *
     * - Cài mới: migration 120200 tạo bảng nền, migration này bổ sung cột.
     * - Cơ sở dữ liệu hiện có: chỉ bổ sung các cột còn thiếu.
     * - Không tạo lại bảng nên không còn lỗi "table already exists".
     */
    public function up(): void
    {
        if (! Schema::hasTable('downtime_reasons')) {
            Schema::create('downtime_reasons', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('category', 50)->default('OTHER');
                $table->boolean('is_planned')->default(false);
                $table->boolean('affects_availability')->default(true);
                $table->boolean('requires_note')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('display_color', 20)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        if (! Schema::hasColumn('downtime_reasons', 'affects_availability')) {
            Schema::table('downtime_reasons', function (Blueprint $table): void {
                $table->boolean('affects_availability')
                    ->default(true)
                    ->after('is_planned');
            });
        }

        if (! Schema::hasColumn('downtime_reasons', 'requires_note')) {
            Schema::table('downtime_reasons', function (Blueprint $table): void {
                $table->boolean('requires_note')
                    ->default(false)
                    ->after('affects_availability');
            });
        }

        if (! Schema::hasColumn('downtime_reasons', 'display_color')) {
            Schema::table('downtime_reasons', function (Blueprint $table): void {
                $table->string('display_color', 20)
                    ->nullable()
                    ->after('is_active');
            });
        }

        if (! Schema::hasColumn('downtime_reasons', 'description')) {
            Schema::table('downtime_reasons', function (Blueprint $table): void {
                $table->text('description')
                    ->nullable()
                    ->after('display_order');
            });
        }

        if (! Schema::hasColumn('downtime_reasons', 'deleted_at')) {
            Schema::table('downtime_reasons', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Không xóa bảng vì bảng gốc thuộc migration 120200.
        // Chỉ gỡ các cột do migration đồng bộ này bổ sung.
        if (! Schema::hasTable('downtime_reasons')) {
            return;
        }

        $columns = array_values(array_filter([
            'affects_availability',
            'requires_note',
            'display_color',
            'description',
            'deleted_at',
        ], fn (string $column): bool => Schema::hasColumn('downtime_reasons', $column)));

        if ($columns !== []) {
            Schema::table('downtime_reasons', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
