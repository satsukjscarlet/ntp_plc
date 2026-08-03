<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\PlcTag;
use App\Services\MonitoringRuntimeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TechnologyMonitoringController extends Controller
{
    public function __construct(
        private readonly MonitoringRuntimeService $runtime
    ) {
    }

    public function index(string $machineCode): View
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $layout = $this->runtime->buildLayout($machine);

        return view('monitoring.runtime.technology', [
            'machine' => $machine,
            'layoutJson' => json_encode(
                $layout,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ]);
    }

    public function data(string $machineCode): JsonResponse
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        return response()->json(
            $this->runtime->currentValues($machine)
        );
    }

    /**
     * Trả dữ liệu lịch sử của một tag để hiển thị Trend trên màn hình
     * Technology.
     */
    public function trend(
        Request $request,
        string $machineCode,
        PlcTag $tag
    ): JsonResponse {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $plcIds = $machine->plcs()->pluck('id');

        abort_unless(
            $plcIds->contains((int) $tag->plc_id),
            404
        );

        if (!$tag->chart_enabled) {
            return response()->json([
                'message' => 'Tag này chưa được bật chức năng biểu đồ.',
            ], 422);
        }

        $validated = $request->validate([
            'range' => [
                'nullable',
                Rule::in(['15m', '1h', '8h', '24h']),
            ],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after:from'],
        ]);

        [$from, $to, $rangeLabel] = $this->resolveTrendRange(
            $validated
        );

        if ($from->diffInDays($to) > 31) {
            return response()->json([
                'message' => 'Khoảng thời gian tối đa là 31 ngày.',
            ], 422);
        }

        $points = $this->loadTrendPoints(
            tagId: $tag->id,
            from: $from,
            to: $to
        );

        $numericValues = $points
            ->pluck('value')
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): float => (float) $value)
            ->values();

        return response()->json([
            'machine' => [
                'id' => $machine->id,
                'code' => $machine->code,
                'name' => $machine->name,
            ],
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->display_name ?: $tag->source_name,
                'address' => $tag->address ?: $tag->source_key,
                'unit' => $tag->display_unit,
                'decimals' => min(
                    max((int) ($tag->display_decimals ?? 1), 0),
                    6
                ),
                'warning_low' => $tag->warning_low !== null
                    ? (float) $tag->warning_low
                    : null,
                'warning_high' => $tag->warning_high !== null
                    ? (float) $tag->warning_high
                    : null,
            ],
            'range' => [
                'key' => $validated['range'] ?? 'custom',
                'label' => $rangeLabel,
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'statistics' => [
                'count' => $numericValues->count(),
                'current' => $numericValues->last(),
                'minimum' => $numericValues->isNotEmpty()
                    ? $numericValues->min()
                    : null,
                'maximum' => $numericValues->isNotEmpty()
                    ? $numericValues->max()
                    : null,
                'average' => $numericValues->isNotEmpty()
                    ? round((float) $numericValues->avg(), 6)
                    : null,
            ],
            'points' => $points->values()->all(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function resolveTrendRange(array $validated): array
    {
        if (
            !empty($validated['from'])
            && !empty($validated['to'])
        ) {
            return [
                Carbon::parse($validated['from']),
                Carbon::parse($validated['to']),
                'Tùy chọn',
            ];
        }

        $range = $validated['range'] ?? '1h';
        $to = now();

        return match ($range) {
            '15m' => [$to->copy()->subMinutes(15), $to, '15 phút'],
            '8h' => [$to->copy()->subHours(8), $to, '8 giờ'],
            '24h' => [$to->copy()->subHours(24), $to, '24 giờ'],
            default => [$to->copy()->subHour(), $to, '1 giờ'],
        };
    }

    /**
     * Đọc lịch sử theo schema hiện tại và tự giảm số điểm để trình duyệt
     * không bị nặng khi xem 8–24 giờ.
     */
    private function loadTrendPoints(
        int $tagId,
        Carbon $from,
        Carbon $to
    ): Collection {
        if (!Schema::hasTable('plc_tag_values')) {
            return collect();
        }

        $columns = Schema::getColumnListing('plc_tag_values');

        $tagColumn = $this->firstExisting(
            $columns,
            ['plc_tag_id', 'tag_id']
        );

        $timeColumn = $this->firstExisting(
            $columns,
            [
                'collected_at',
                'recorded_at',
                'source_time',
                'created_at',
            ]
        );

        $valueCandidates = array_values(array_filter([
            in_array('numeric_value', $columns, true)
                ? 'numeric_value'
                : null,
            in_array('value_numeric', $columns, true)
                ? 'value_numeric'
                : null,
            in_array('raw_value', $columns, true)
                ? 'raw_value'
                : null,
            in_array('value', $columns, true)
                ? 'value'
                : null,
        ]));

        if (
            !$tagColumn
            || !$timeColumn
            || $valueCandidates === []
        ) {
            return collect();
        }

        $query = DB::table('plc_tag_values')
            ->where($tagColumn, $tagId)
            ->whereBetween($timeColumn, [$from, $to])
            ->orderBy($timeColumn);

        $grammar = $query->getGrammar();

        $wrappedValues = array_map(
            fn (string $column): string =>
                $grammar->wrap($column),
            $valueCandidates
        );

        $qualityColumn = $this->firstExisting(
            $columns,
            ['quality', 'data_quality']
        );

        if ($qualityColumn) {
            $query->where(function ($qualityQuery) use (
                $qualityColumn
            ): void {
                $qualityQuery
                    ->whereNull($qualityColumn)
                    ->orWhereIn(
                        DB::raw('UPPER(' . $qualityColumn . ')'),
                        ['GOOD', 'OK']
                    );
            });
        }

        $rows = $query
            ->select([
                DB::raw(
                    $grammar->wrap($timeColumn)
                    . ' as collected_at'
                ),
                DB::raw(
                    'COALESCE('
                    . implode(', ', $wrappedValues)
                    . ') as point_value'
                ),
            ])
            ->get()
            ->filter(
                fn ($row): bool =>
                    $row->point_value !== null
                    && is_numeric($row->point_value)
            )
            ->values();

        $maxPoints = 1200;

        if ($rows->count() > $maxPoints) {
            $step = (int) ceil($rows->count() / $maxPoints);

            $rows = $rows
                ->filter(
                    fn ($row, int $index): bool =>
                        $index % $step === 0
                        || $index === $rows->count() - 1
                )
                ->values();
        }

        return $rows->map(function ($row): array {
            return [
                'time' => Carbon::parse(
                    $row->collected_at
                )->toIso8601String(),
                'value' => (float) $row->point_value,
            ];
        });
    }

    private function firstExisting(
        array $columns,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
