<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_production_declarations', function (Blueprint $table): void {
            if (!Schema::hasColumn('shift_production_declarations', 'returned_by')) {
                $table->foreignId('returned_by')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('users', 'id', 'fk_spd_returned_by')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('shift_production_declarations', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('returned_by');
            }

            if (!Schema::hasColumn('shift_production_declarations', 'return_reason')) {
                $table->text('return_reason')->nullable()->after('returned_at');
            }

            if (!Schema::hasColumn('shift_production_declarations', 'locked_by')) {
                $table->foreignId('locked_by')
                    ->nullable()
                    ->after('return_reason')
                    ->constrained('users', 'id', 'fk_spd_locked_by')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('shift_production_declarations', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('locked_by');
            }

            if (!Schema::hasColumn('shift_production_declarations', 'unlocked_by')) {
                $table->foreignId('unlocked_by')
                    ->nullable()
                    ->after('locked_at')
                    ->constrained('users', 'id', 'fk_spd_unlocked_by')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('shift_production_declarations', 'unlocked_at')) {
                $table->timestamp('unlocked_at')->nullable()->after('unlocked_by');
            }

            if (!Schema::hasColumn('shift_production_declarations', 'unlock_reason')) {
                $table->text('unlock_reason')->nullable()->after('unlocked_at');
            }
        });

        Schema::create('shift_production_declaration_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shift_production_declaration_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('reason')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign(
                'shift_production_declaration_id',
                'fk_spd_audit_declaration'
            )->references('id')
                ->on('shift_production_declarations')
                ->cascadeOnDelete();

            $table->foreign(
                'user_id',
                'fk_spd_audit_user'
            )->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['shift_production_declaration_id', 'created_at'],
                'idx_spd_audit_declaration_time'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_production_declaration_audits');

        Schema::table('shift_production_declarations', function (Blueprint $table): void {
            $foreignColumns = [
                'returned_by',
                'locked_by',
                'unlocked_by',
            ];

            foreach ($foreignColumns as $column) {
                if (Schema::hasColumn('shift_production_declarations', $column)) {
                    $table->dropForeign([$column]);
                }
            }

            $columns = [
                'returned_by',
                'returned_at',
                'return_reason',
                'locked_by',
                'locked_at',
                'unlocked_by',
                'unlocked_at',
                'unlock_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('shift_production_declarations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
