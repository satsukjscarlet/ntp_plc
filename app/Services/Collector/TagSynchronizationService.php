<?php

namespace App\Services\Collector;

use App\Models\Plc;
use App\Models\PlcTag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagSynchronizationService
{
    public function synchronize(Plc $plc, array $incomingTags): array
    {
        $startedAt = microtime(true);
        $now = Carbon::now();

        $result = [
            'total_received' => count($incomingTags),
            'new' => 0,
            'updated' => 0,
            'matched' => 0,
            'removed' => 0,
            'skipped' => 0,
            'duplicate' => 0,
            'items' => [],
        ];

        DB::transaction(function () use (
            $plc,
            $incomingTags,
            $now,
            &$result
        ): void {
            $seenIds = [];
            $processedKeys = [];

            foreach ($incomingTags as $incoming) {
                $tagData = $this->normalizeIncomingTag($incoming);

                if ($tagData === null) {
                    $result['skipped']++;
                    continue;
                }

                if (isset($processedKeys[$tagData['source_key']])) {
                    $result['duplicate']++;
                    continue;
                }

                $processedKeys[$tagData['source_key']] = true;

                $tag = $this->findExistingTag($plc, $tagData);

                if ($tag === null) {
                    $tag = $this->createTag($plc, $tagData, $now);
                    $result['new']++;
                } else {
                    $changed = $this->updateTag($tag, $tagData, $now);

                    if ($changed) {
                        $result['updated']++;
                    } else {
                        $result['matched']++;
                    }
                }

                $seenIds[] = $tag->id;

                $result['items'][] = [
                    'id' => $tag->id,
                    'address' => $tag->address,
                    'source_name' => $tag->source_name,
                    'display_name' => $tag->display_name,
                    'data_type' => $tag->data_type,
                    'sync_status' => $tag->sync_status,
                ];
            }

            if ($seenIds !== []) {
                $staleTags = PlcTag::query()
                    ->where('plc_id', $plc->id)
                    ->where('is_active', true)
                    ->whereNotIn('id', $seenIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($staleTags as $staleTag) {
                    $staleTag->is_active = false;
                    $staleTag->sync_status = 'REMOVED';
                    $staleTag->save();

                    $result['removed']++;
                }
            }
        });

        $result['duration_ms'] = (int) round(
            (microtime(true) - $startedAt) * 1000
        );

        return $result;
    }

    private function normalizeIncomingTag(array $incoming): ?array
    {
        $address = $this->clean($incoming['address'] ?? null);
        $sourceName = $this->clean($incoming['source_name'] ?? null);
        $portalName = $this->clean($incoming['portal_name'] ?? null);
        $dataType = $this->clean($incoming['data_type'] ?? null);
        $unit = $this->clean($incoming['unit'] ?? null);
        $comment = $this->clean($incoming['comment'] ?? null);

        if ($address === null && $sourceName === null) {
            return null;
        }

        $sourceName ??= $address;
        $portalName ??= $sourceName;

        if ($sourceName === null) {
            return null;
        }

        return [
            'address' => $address,
            'source_name' => $sourceName,
            'portal_name' => $portalName,
            'data_type' => $dataType,
            'unit' => $unit,
            'comment' => $comment,
            'source_key' => $this->makeSourceKey($address, $sourceName),
        ];
    }

    private function findExistingTag(Plc $plc, array $data): ?PlcTag
    {
        return PlcTag::query()
            ->where('plc_id', $plc->id)
            ->where(function ($query) use ($data): void {
                $query->where('source_key', $data['source_key']);

                if ($data['address'] !== null) {
                    $query->orWhere('address', $data['address']);
                }

                $query->orWhere('source_name', $data['source_name']);
            })
            ->lockForUpdate()
            ->first();
    }

    private function createTag(
        Plc $plc,
        array $data,
        Carbon $now
    ): PlcTag {
        $tag = new PlcTag();

        $tag->plc_id = $plc->id;
        $tag->address = $data['address'];
        $tag->source_name = $data['source_name'];
        $tag->portal_name = $data['portal_name'];
        $tag->display_name = $data['portal_name'] ?: $data['source_name'];
        $tag->data_type = $data['data_type'];
        $tag->unit = $data['unit'];
        $tag->source_key = $data['source_key'];
        $tag->sync_status = 'NEW';
        $tag->last_seen_at = $now;
        $tag->is_active = true;
        $tag->is_visible = true;

        if ($this->hasAttribute($tag, 'comment')) {
            $tag->comment = $data['comment'];
        }

        $tag->save();

        return $tag;
    }

    private function updateTag(
        PlcTag $tag,
        array $data,
        Carbon $now
    ): bool {
        $changed = false;

        foreach ([
            'address',
            'source_name',
            'portal_name',
            'data_type',
            'unit',
            'source_key',
        ] as $field) {
            if ($tag->{$field} !== $data[$field]) {
                $tag->{$field} = $data[$field];
                $changed = true;
            }
        }

        if ($this->hasAttribute($tag, 'comment')
            && $tag->comment !== $data['comment']) {
            $tag->comment = $data['comment'];
            $changed = true;
        }

        if (!$tag->is_active) {
            $tag->is_active = true;
            $changed = true;
        }

        $tag->last_seen_at = $now;
        $tag->sync_status = $changed ? 'RENAMED' : 'MATCHED';
        $tag->save();

        return $changed;
    }

    private function makeSourceKey(
        ?string $address,
        string $sourceName
    ): string {
        if ($address !== null) {
            return 'address:' . Str::lower($address);
        }

        $normalizedName = Str::lower(
            trim(preg_replace('/\s+/u', ' ', $sourceName) ?? $sourceName)
        );

        return 'name:' . hash('sha256', $normalizedName);
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(
            preg_replace('/\s+/u', ' ', (string) $value)
                ?? (string) $value
        );

        return $value === '' ? null : $value;
    }

    private function hasAttribute(PlcTag $tag, string $attribute): bool
    {
        return array_key_exists($attribute, $tag->getAttributes())
            || in_array($attribute, $tag->getFillable(), true);
    }
}
