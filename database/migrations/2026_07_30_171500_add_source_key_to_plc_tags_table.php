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
        if (!Schema::hasColumn('plc_tags', 'source_key')) {
            Schema::table('plc_tags', function (Blueprint $table) {
                $table->string('source_key', 255)
                    ->nullable()
                    ->after('address');
            });
        }

        /*
         * Backfill dữ liệu hiện có.
         *
         * Có Address:
         *      source_key = ADDRESS::<ĐỊA_CHỈ>
         *
         * Không Address:
         *      source_key = NAME::<TÊN_CHUẨN_HÓA>
         */
        DB::table('plc_tags')
            ->orderBy('id')
            ->chunkById(200, function ($tags): void {
                foreach ($tags as $tag) {
                    $address = trim((string) ($tag->address ?? ''));
                    $name = trim((string) (
                        $tag->source_name
                        ?? $tag->portal_name
                        ?? $tag->display_name
                        ?? ''
                    ));

                    $sourceKey = $address !== ''
                        ? 'ADDRESS::' . mb_strtoupper(preg_replace('/\s+/u', '', $address))
                        : 'NAME::' . Str::upper(
                            Str::ascii(
                                preg_replace('/\s+/u', ' ', $name)
                            )
                        );

                    /*
                     * Tránh xung đột khi có hai tag cùng tên không Address.
                     */
                    $exists = DB::table('plc_tags')
                        ->where('plc_id', $tag->plc_id)
                        ->where('source_key', $sourceKey)
                        ->where('id', '<>', $tag->id)
                        ->exists();

                    if ($exists) {
                        $sourceKey .= '::' . $tag->id;
                    }

                    DB::table('plc_tags')
                        ->where('id', $tag->id)
                        ->update(['source_key' => $sourceKey]);
                }
            });

        Schema::table('plc_tags', function (Blueprint $table) {
            $table->unique(
                ['plc_id', 'source_key'],
                'plc_tags_plc_id_source_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('plc_tags', function (Blueprint $table) {
            $table->dropUnique('plc_tags_plc_id_source_key_unique');
            $table->dropColumn('source_key');
        });
    }
};
