<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username', 100)
                    ->nullable()
                    ->after('name');
            });
        }

        $used = [];

        DB::table('users')
            ->select(['id', 'employee_code', 'email'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use (&$used): void {
                $base = $this->makeUsernameBase($user);
                $username = $base;
                $suffix = 1;

                while (
                    isset($used[mb_strtolower($username)])
                    || DB::table('users')
                        ->where('username', $username)
                        ->where('id', '<>', $user->id)
                        ->exists()
                ) {
                    $suffix++;
                    $username = Str::limit(
                        $base,
                        100 - strlen((string) $suffix) - 1,
                        ''
                    ) . '-' . $suffix;
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);

                $used[mb_strtolower($username)] = true;
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 100)
                ->nullable(false)
                ->change();

            $table->string('email')
                ->nullable()
                ->change();
        });

        if (!$this->indexExists('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('username', 'users_username_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_username_unique');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')
                ->nullable(false)
                ->change();

            $table->dropColumn('username');
        });
    }

    private function makeUsernameBase(object $user): string
    {
        $candidate = trim((string) ($user->employee_code ?? ''));

        if ($candidate === '' && !empty($user->email)) {
            $candidate = Str::before((string) $user->email, '@');
        }

        if ($candidate === '') {
            $candidate = 'user-' . $user->id;
        }

        $candidate = Str::ascii($candidate);
        $candidate = mb_strtolower($candidate);
        $candidate = preg_replace('/[^a-z0-9._-]+/', '-', $candidate) ?: '';
        $candidate = trim($candidate, '.-_');

        return Str::limit(
            $candidate !== '' ? $candidate : 'user-' . $user->id,
            100,
            ''
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
