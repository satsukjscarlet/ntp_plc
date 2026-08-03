<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'shift_production_declarations';

    public function up(): void
    {
        /*
         * Migration trước đã có thể thêm một phần các cột rồi mới dừng ở bước
         * DROP INDEX. Vì vậy migration này phải chạy lại an toàn trên cả:
         *
         * 1. Database chưa chạy migration lần nào.
         * 2. Database đã thêm cột nhưng migration bị FAIL giữa chừng.
         */

        Schema::table($this->table, function (Blueprint $table): void {
            if (!Schema::hasColumn($this->table, 'record_scope')) {
                $table->unsignedBigInteger('record_scope')
                    ->default(0)
                    ->after('production_date');
            }

            if (!Schema::hasColumn($this->table, 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')
                    ->nullable()
                    ->after('calculation_note');
            }

            if (!Schema::hasColumn($this->table, 'delete_reason')) {
                $table->text('delete_reason')
                    ->nullable()
                    ->after('deleted_by');
            }

            if (!Schema::hasColumn($this->table, 'deleted_at')) {
                $table->softDeletes();
            }
        });

        /*
         * `shift_declaration_unique` đang đồng thời được MySQL dùng làm index
         * hỗ trợ cho các foreign key machine_id và production_shift_id.
         *
         * Phải tạo index riêng cho từng FK trước khi bỏ unique index cũ.
         */
        $this->createIndexIfMissing(
            'idx_spd_machine_fk',
            ['machine_id']
        );

        $this->createIndexIfMissing(
            'idx_spd_shift_fk',
            ['production_shift_id']
        );

        /*
         * Cột deleted_by được thêm thủ công để migration có thể chạy lại sau
         * lần FAIL. Tạo foreign key riêng nếu chưa tồn tại.
         */
        if (!$this->foreignKeyExists('fk_spd_deleted_by')) {
            DB::statement(
                'ALTER TABLE `' . $this->table . '`
                 ADD CONSTRAINT `fk_spd_deleted_by`
                 FOREIGN KEY (`deleted_by`)
                 REFERENCES `users` (`id`)
                 ON DELETE SET NULL'
            );
        }

        if ($this->indexExists('shift_declaration_unique')) {
            DB::statement(
                'ALTER TABLE `' . $this->table . '`
                 DROP INDEX `shift_declaration_unique`'
            );
        }

        $this->createUniqueIndexIfMissing(
            'shift_declaration_active_unique',
            [
                'machine_id',
                'production_shift_id',
                'production_date',
                'record_scope',
            ]
        );

        $this->createIndexIfMissing(
            'idx_spd_deleted_date',
            ['deleted_at', 'production_date']
        );
    }

    public function down(): void
    {
        /*
         * Chỉ rollback khi không có hai bản ghi trùng Máy - Ca - Ngày.
         * Thông thường production không cần rollback migration này.
         */

        if ($this->indexExists('shift_declaration_active_unique')) {
            DB::statement(
                'ALTER TABLE `' . $this->table . '`
                 DROP INDEX `shift_declaration_active_unique`'
            );
        }

        $this->createUniqueIndexIfMissing(
            'shift_declaration_unique',
            ['machine_id', 'production_shift_id', 'production_date']
        );

        if ($this->indexExists('idx_spd_deleted_date')) {
            DB::statement(
                'ALTER TABLE `' . $this->table . '`
                 DROP INDEX `idx_spd_deleted_date`'
            );
        }

        if ($this->foreignKeyExists('fk_spd_deleted_by')) {
            DB::statement(
                'ALTER TABLE `' . $this->table . '`
                 DROP FOREIGN KEY `fk_spd_deleted_by`'
            );
        }

        Schema::table($this->table, function (Blueprint $table): void {
            foreach ([
                'deleted_by',
                'delete_reason',
                'deleted_at',
                'record_scope',
            ] as $column) {
                if (Schema::hasColumn($this->table, $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        /*
         * Giữ lại idx_spd_machine_fk và idx_spd_shift_fk vì đây là index hỗ trợ
         * foreign key, không gây ảnh hưởng và tránh MySQL lỗi 1553 khi rollback.
         */
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $this->table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $this->table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }

    private function createIndexIfMissing(
        string $indexName,
        array $columns
    ): void {
        if ($this->indexExists($indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            $this->table,
            $indexName,
            $this->quoteColumns($columns)
        ));
    }

    private function createUniqueIndexIfMissing(
        string $indexName,
        array $columns
    ): void {
        if ($this->indexExists($indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD UNIQUE INDEX `%s` (%s)',
            $this->table,
            $indexName,
            $this->quoteColumns($columns)
        ));
    }

    private function quoteColumns(array $columns): string
    {
        return collect($columns)
            ->map(fn (string $column): string => '`' . $column . '`')
            ->implode(', ');
    }
};
