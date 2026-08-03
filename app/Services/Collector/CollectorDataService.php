<?php

namespace App\Services\Collector;

use App\Models\Plc;
use App\Models\PlcCollectionLog;
use App\Models\PlcCurrentValue;
use App\Models\PlcTag;
use App\Models\PlcTagValue;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CollectorDataService
{
    private array $columnCache = [];

    public function store(Plc $plc, array $payload): array
    {
        $batchCollectedAt = CarbonImmutable::parse($payload['collected_at']);
        $durationMs = (int) ($payload['duration_ms'] ?? 0);
        $values = $payload['values'] ?? [];

        $currentCount = 0;
        $historyCount = 0;
        $unchangedCount = 0;
        $historyDisabledCount = 0;
        $autoCreatedCount = 0;
        $matchedLegacyCount = 0;

        DB::beginTransaction();

        try {
            foreach ($values as $item) {
                $sourceKey = trim((string) ($item['source_key'] ?? ''));

                if ($sourceKey === '') {
                    continue;
                }

                $tag = $this->findExistingTag($plc, $item, $sourceKey);

                if (!$tag) {
                    $tag = $this->createTag($plc, $item, $sourceKey);
                    $autoCreatedCount++;
                } else {
                    if ((string) $tag->source_key !== $sourceKey) {
                        $matchedLegacyCount++;
                    }

                    $this->refreshTagMetadata($tag, $item, $sourceKey);
                }

                $collectedAt = !empty($item['collected_at'])
                    ? CarbonImmutable::parse($item['collected_at'])
                    : $batchCollectedAt;

                $dataType = strtoupper(trim((string) (
                    $item['data_type'] ?? $tag->data_type ?? ''
                )));

                $value = $this->normalizeComparableValue(
                    $item['value'] ?? null,
                    $dataType,
                );

                $rawValue = $this->stringifyValue(
                    $item['raw_value'] ?? $item['value'] ?? null
                );

                $quality = strtoupper(trim((string) (
                    $item['quality'] ?? 'GOOD'
                )));

                $current = PlcCurrentValue::query()
                    ->where('plc_tag_id', $tag->id)
                    ->lockForUpdate()
                    ->first();

                if (!$current) {
                    $current = new PlcCurrentValue();
                    $current->plc_tag_id = $tag->id;
                }

                $historyEnabled = $this->historyEnabled($tag);

                /*
                 * Collector V3:
                 * So sánh với history_base_value nằm ngay trong
                 * plc_current_values. Không truy vấn plc_tag_values.
                 */
                $shouldWriteHistory = $historyEnabled
                    && $this->historyValueChanged(
                        current: $current,
                        incomingValue: $value,
                        incomingQuality: $quality,
                        dataType: $dataType,
                        deadband: $this->historyDeadband($tag),
                    );

                /*
                 * Current luôn cập nhật mỗi chu kỳ.
                 * history_base chỉ cập nhật khi ghi lịch sử.
                 */
                $this->saveCurrentValue(
                    current: $current,
                    tagId: $tag->id,
                    value: $value,
                    rawValue: $rawValue,
                    quality: $quality,
                    collectedAt: $collectedAt,
                    updateHistoryBase: $shouldWriteHistory,
                );

                $currentCount++;

                if (!$historyEnabled) {
                    $historyDisabledCount++;
                    continue;
                }

                if ($shouldWriteHistory) {
                    $this->saveHistoryValue(
                        tagId: $tag->id,
                        value: $value,
                        rawValue: $rawValue,
                        quality: $quality,
                        collectedAt: $collectedAt,
                    );

                    $historyCount++;
                } else {
                    $unchangedCount++;
                }
            }

            $this->writeCollectionLog(
                plc: $plc,
                status: 'SUCCESS',
                totalTags: count($values),
                currentValues: $currentCount,
                historyValues: $historyCount,
                durationMs: $durationMs,
                message: $this->buildSuccessMessage(
                    autoCreatedCount: $autoCreatedCount,
                    unchangedCount: $unchangedCount,
                    matchedLegacyCount: $matchedLegacyCount,
                    historyDisabledCount: $historyDisabledCount,
                ),
                collectedAt: $batchCollectedAt,
            );

            DB::commit();

            return [
                'success' => true,
                'plc_id' => $plc->id,
                'total_tags' => count($values),
                'current_values' => $currentCount,
                'history_values' => $historyCount,
                'unchanged_values' => $unchangedCount,
                'history_disabled_values' => $historyDisabledCount,
                'auto_created_tags' => $autoCreatedCount,
                'matched_legacy_tags' => $matchedLegacyCount,
                'collector_version' => 3,
                'collected_at' => $batchCollectedAt->toIso8601String(),
            ];
        } catch (Throwable $exception) {
            DB::rollBack();

            $this->writeCollectionLog(
                plc: $plc,
                status: 'FAILED',
                totalTags: count($values),
                currentValues: $currentCount,
                historyValues: $historyCount,
                durationMs: $durationMs,
                message: $exception->getMessage(),
                collectedAt: $batchCollectedAt,
            );

            throw $exception;
        }
    }

    public function heartbeat(Plc $plc, array $payload): array
    {
        $status = strtoupper(trim((string) ($payload['status'] ?? 'ONLINE')));
        $sentAt = !empty($payload['sent_at'])
            ? CarbonImmutable::parse($payload['sent_at'])
            : now();

        $updates = [];

        if ($this->hasColumn('plcs', 'last_seen_at')) {
            $updates['last_seen_at'] = $sentAt;
        }

        if ($this->hasColumn('plcs', 'status')) {
            $updates['status'] = $status;
        }

        if ($this->hasColumn('plcs', 'last_error')) {
            $updates['last_error'] = $status === 'ERROR'
                ? ($payload['message'] ?? null)
                : null;
        }

        if ($updates !== []) {
            $plc->forceFill($updates)->save();
        }

        return [
            'success' => true,
            'plc_id' => $plc->id,
            'status' => $status,
            'last_seen_at' => $sentAt->toIso8601String(),
        ];
    }

    private function historyValueChanged(
        PlcCurrentValue $current,
        ?string $incomingValue,
        string $incomingQuality,
        string $dataType,
        float $deadband,
    ): bool {
        $baseValue = $this->hasColumn(
            'plc_current_values',
            'history_base_value'
        )
            ? $current->history_base_value
            : null;

        $baseQuality = $this->hasColumn(
            'plc_current_values',
            'history_base_quality'
        )
            ? $current->history_base_quality
            : null;

        /*
         * Lần đầu chạy V3: chưa có giá trị gốc nên ghi một bản lịch sử
         * và khởi tạo history_base_*.
         */
        if ($baseValue === null && $baseQuality === null) {
            return true;
        }

        $normalizedBaseValue = $this->normalizeComparableValue(
            $baseValue,
            $dataType,
        );

        $normalizedBaseQuality = strtoupper(trim((string) (
            $baseQuality ?? 'GOOD'
        )));

        if ($normalizedBaseQuality !== $incomingQuality) {
            return true;
        }

        if (
            $this->isNumericType($dataType)
            && is_numeric($normalizedBaseValue)
            && is_numeric($incomingValue)
        ) {
            return abs(
                (float) $incomingValue - (float) $normalizedBaseValue
            ) >= max(0, $deadband);
        }

        return $normalizedBaseValue !== $incomingValue;
    }

    private function saveCurrentValue(
        PlcCurrentValue $current,
        int $tagId,
        ?string $value,
        ?string $rawValue,
        string $quality,
        CarbonImmutable $collectedAt,
        bool $updateHistoryBase,
    ): void {
        $attributes = [
            'plc_tag_id' => $tagId,
            'value' => $value,
            'value_text' => $value,
            'value_string' => $value,
            'raw_value' => $rawValue,
            'quality' => $quality,
            'collected_at' => $collectedAt,
        ];

        if ($updateHistoryBase) {
            $attributes['history_base_value'] = $value;
            $attributes['history_base_quality'] = $quality;
            $attributes['last_history_at'] = $collectedAt;
        }

        $current->forceFill(
            $this->filterExistingColumns(
                'plc_current_values',
                $attributes
            )
        );
        $current->save();
    }

    private function historyEnabled(PlcTag $tag): bool
    {
        if (!$this->hasColumn('plc_tags', 'history_enabled')) {
            return true;
        }

        return (bool) $tag->history_enabled;
    }

    private function historyDeadband(PlcTag $tag): float
    {
        if (!$this->hasColumn('plc_tags', 'history_deadband')) {
            return 0.0;
        }

        return max(0, (float) ($tag->history_deadband ?? 0));
    }

    private function isNumericType(string $dataType): bool
    {
        return in_array(strtoupper($dataType), [
            'REAL', 'LREAL', 'FLOAT', 'DOUBLE',
            'BYTE', 'WORD', 'DWORD',
            'INT', 'DINT', 'UDINT',
            'SINT', 'USINT', 'UINT',
            'LINT', 'ULINT',
        ], true);
    }

    private function findExistingTag(
        Plc $plc,
        array $item,
        string $sourceKey,
    ): ?PlcTag {
        $address = $this->clean($item['address'] ?? null);
        $sourceName = $this->clean($item['source_name'] ?? null);

        return PlcTag::query()
            ->where('plc_id', $plc->id)
            ->where(function ($query) use (
                $sourceKey,
                $address,
                $sourceName
            ): void {
                $query->where('source_key', $sourceKey);

                if ($address !== null) {
                    $query->orWhere('address', $address);
                }

                if ($sourceName !== null) {
                    $query->orWhere('source_name', $sourceName);
                }
            })
            ->lockForUpdate()
            ->first();
    }

    private function createTag(
        Plc $plc,
        array $item,
        string $sourceKey,
    ): PlcTag {
        $sourceName = trim((string) (
            $item['source_name']
            ?? $item['address']
            ?? $sourceKey
        ));

        $attributes = [
            'plc_id' => $plc->id,
            'source_key' => $sourceKey,
            'address' => $item['address'] ?? null,
            'source_name' => $sourceName,
            'portal_name' => $sourceName,
            'display_name' => $sourceName,
            'data_type' => $item['data_type'] ?? null,
            'display_format' => $item['display_format'] ?? null,
            'unit' => $item['unit'] ?? null,
            'comment' => $item['comment'] ?? null,
            'sync_status' => 'NEW',
            'is_active' => true,
            'is_visible' => true,
            'history_enabled' => true,
            'history_deadband' => 0,
            'last_seen_at' => now(),
            'last_synced_at' => now(),
        ];

        $tag = new PlcTag();
        $tag->forceFill(
            $this->filterExistingColumns('plc_tags', $attributes)
        );
        $tag->save();

        return $tag;
    }

    private function refreshTagMetadata(
        PlcTag $tag,
        array $item,
        string $sourceKey,
    ): void {
        $updates = [
            'source_key' => $sourceKey,
            'address' => $item['address'] ?? $tag->address,
            'source_name' => $item['source_name'] ?? $tag->source_name,
            'portal_name' => $item['source_name'] ?? $tag->portal_name,
            'data_type' => $item['data_type'] ?? $tag->data_type,
            'display_format' => $item['display_format'] ?? null,
            'unit' => $item['unit'] ?? null,
            'comment' => $item['comment'] ?? null,
            'sync_status' => 'MATCHED',
            'is_active' => true,
            'last_seen_at' => now(),
            'last_synced_at' => now(),
        ];

        $tag->forceFill(
            $this->filterExistingColumns('plc_tags', $updates)
        );

        if ($tag->isDirty()) {
            $tag->save();
        }
    }

    private function normalizeComparableValue(
        mixed $value,
        ?string $dataType,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $type = strtoupper(trim((string) $dataType));

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        if ($type === 'BOOL') {
            return match (strtolower($text)) {
                '1', 'true', 'on', 'yes' => '1',
                '0', 'false', 'off', 'no' => '0',
                default => strtolower($text),
            };
        }

        if (in_array(
            $type,
            ['REAL', 'LREAL', 'FLOAT', 'DOUBLE'],
            true
        )) {
            $numeric = str_replace(',', '.', $text);

            if (is_numeric($numeric)) {
                $normalized = rtrim(
                    rtrim(
                        number_format(
                            (float) $numeric,
                            10,
                            '.',
                            ''
                        ),
                        '0'
                    ),
                    '.'
                );

                return $normalized === '-0'
                    ? '0'
                    : $normalized;
            }
        }

        if (
            in_array($type, [
                'BYTE', 'WORD', 'DWORD',
                'INT', 'DINT', 'UDINT',
                'SINT', 'USINT', 'UINT',
                'LINT', 'ULINT',
            ], true)
            && preg_match('/^[+-]?\d+$/', $text)
        ) {
            return (string) ((int) $text);
        }

        return preg_replace('/\s+/', ' ', $text);
    }

    private function saveHistoryValue(
        int $tagId,
        ?string $value,
        ?string $rawValue,
        string $quality,
        CarbonImmutable $collectedAt,
    ): void {
        $attributes = [
            'plc_tag_id' => $tagId,
            'value' => $value,
            'value_text' => $value,
            'value_string' => $value,
            'raw_value' => $rawValue,
            'quality' => $quality,
            'collected_at' => $collectedAt,
        ];

        $history = new PlcTagValue();
        $history->forceFill(
            $this->filterExistingColumns(
                'plc_tag_values',
                $attributes
            )
        );
        $history->save();
    }

    private function stringifyValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }

    private function filterExistingColumns(
        string $table,
        array $attributes,
    ): array {
        return array_filter(
            $attributes,
            fn (mixed $value, string $column): bool =>
                $this->hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function hasColumn(
        string $table,
        string $column,
    ): bool {
        $key = "{$table}.{$column}";

        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasColumn(
                $table,
                $column
            );
        }

        return $this->columnCache[$key];
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function buildSuccessMessage(
        int $autoCreatedCount,
        int $unchangedCount,
        int $matchedLegacyCount,
        int $historyDisabledCount,
    ): ?string {
        $parts = [];

        if ($autoCreatedCount > 0) {
            $parts[] = "Tự tạo {$autoCreatedCount} tag.";
        }

        if ($matchedLegacyCount > 0) {
            $parts[] = "Chuẩn hóa {$matchedLegacyCount} tag cũ.";
        }

        if ($unchangedCount > 0) {
            $parts[] = "{$unchangedCount} giá trị chưa vượt deadband.";
        }

        if ($historyDisabledCount > 0) {
            $parts[] = "{$historyDisabledCount} tag tắt lưu lịch sử.";
        }

        return $parts === []
            ? null
            : implode(' ', $parts);
    }

    private function writeCollectionLog(
        Plc $plc,
        string $status,
        int $totalTags,
        int $currentValues,
        int $historyValues,
        int $durationMs,
        ?string $message,
        CarbonImmutable $collectedAt,
    ): void {
        try {
            $attributes = [
                'plc_id' => $plc->id,
                'status' => $status,
                'total_tags' => $totalTags,
                'current_values' => $currentValues,
                'history_values' => $historyValues,
                'duration_ms' => $durationMs,
                'message' => $message,
                'collected_at' => $collectedAt,
            ];

            $log = new PlcCollectionLog();
            $log->forceFill(
                $this->filterExistingColumns(
                    'plc_collection_logs',
                    $attributes
                )
            );
            $log->save();
        } catch (Throwable) {
            // Không làm hỏng collector nếu bảng log khác cấu trúc.
        }
    }
}
