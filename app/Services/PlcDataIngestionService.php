<?php

namespace App\Services;

use App\Models\Plc;
use App\Models\PlcCollectionLog;
use App\Models\PlcCurrentValue;
use App\Models\PlcTag;
use App\Models\PlcTagValue;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlcDataIngestionService
{
    public function ingest(Plc $plc, array $payload): array
    {
        $started = microtime(true);

        $result = [
            'total_tags' => count($payload['values']),
            'current_values' => 0,
            'history_values' => 0,
            'unknown_tags' => 0,
            'unknown_items' => [],
        ];

        DB::transaction(function () use ($plc, $payload, &$result): void {
            foreach ($payload['values'] as $item) {
                $tag = $this->resolveTag($plc, $item);

                if (!$tag) {
                    $result['unknown_tags']++;
                    $result['unknown_items'][] = [
                        'tag_id' => $item['tag_id'] ?? null,
                        'source_name' => $item['source_name'] ?? null,
                        'address' => $item['address'] ?? null,
                    ];
                    continue;
                }

                $valueData = $this->normalizeValueData($item);

                PlcCurrentValue::query()->updateOrCreate(
                    ['plc_tag_id' => $tag->id],
                    $valueData
                );

                $result['current_values']++;

                if (($item['save_history'] ?? true) === true) {
                    PlcTagValue::query()->create(
                        array_merge(
                            ['plc_tag_id' => $tag->id],
                            $valueData
                        )
                    );

                    $result['history_values']++;
                }
            }

            $plc->update([
                'last_seen_at' => now(),
                'connection_status' => 'ONLINE',
                'last_error' => null,
            ]);
        });

        $durationMs = isset($payload['duration_ms'])
            ? (int) $payload['duration_ms']
            : (int) round((microtime(true) - $started) * 1000);

        PlcCollectionLog::query()->create([
            'plc_id' => $plc->id,
            'status' => 'SUCCESS',
            'total_tags' => $result['total_tags'],
            'current_values' => $result['current_values'],
            'history_values' => $result['history_values'],
            'unknown_tags' => $result['unknown_tags'],
            'duration_ms' => $durationMs,
            'collector_name' => $payload['collector_name'] ?? null,
            'collector_version' => $payload['collector_version'] ?? null,
            'request_id' => $payload['request_id'] ?? null,
            'message' => $result['unknown_tags'] > 0
                ? 'Có tag chưa khớp với cấu hình PLC.'
                : 'Thu thập dữ liệu thành công.',
            'metadata' => [
                'unknown_items' => array_slice(
                    $result['unknown_items'],
                    0,
                    100
                ),
            ],
            'started_at' => $payload['started_at'] ?? null,
            'finished_at' => $payload['finished_at'] ?? now(),
        ]);

        $result['duration_ms'] = $durationMs;

        return $result;
    }

    private function resolveTag(Plc $plc, array $item): ?PlcTag
    {
        if (!empty($item['tag_id'])) {
            return $plc->tags()
                ->whereKey($item['tag_id'])
                ->where('is_active', true)
                ->first();
        }

        if (!empty($item['source_name'])) {
            $tag = $plc->tags()
                ->where('source_name', $item['source_name'])
                ->where('is_active', true)
                ->first();

            if ($tag) {
                return $tag;
            }
        }

        if (!empty($item['address'])) {
            return $plc->tags()
                ->where('address', $item['address'])
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    private function normalizeValueData(array $item): array
    {
        $rawValue = array_key_exists('raw_value', $item)
            ? $item['raw_value']
            : null;

        if (is_array($rawValue) || is_object($rawValue)) {
            $rawValue = json_encode(
                $rawValue,
                JSON_UNESCAPED_UNICODE
            );
        }

        return [
            'raw_value' => $rawValue === null
                ? null
                : (string) $rawValue,

            'numeric_value' =>
                $item['numeric_value'] ?? null,

            'text_value' =>
                $item['text_value'] ?? null,

            'quality' =>
                $item['quality'] ?? 'GOOD',

            'collected_at' =>
                CarbonImmutable::parse(
                    $item['collected_at']
                ),
        ];
    }
}
