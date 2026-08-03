<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Plant;
use App\Models\ProductionLine;
use App\Models\ProductionShift;
use App\Models\ShiftProductionDeclaration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $this->filters($request);

        $allDeclarationsQuery = ShiftProductionDeclaration::query()
            ->with([
                'machine.productionLine.plant',
                'shift',
                'items',
            ])
            ->whereBetween('production_date', [
                $filters['date_from'],
                $filters['date_to'],
            ]);

        $this->applyFilters($allDeclarationsQuery, $filters);

        $allDeclarations = $allDeclarationsQuery
            ->orderBy('production_date')
            ->orderBy('machine_id')
            ->orderBy('production_shift_id')
            ->get();

        $declarations = $allDeclarations
            ->whereNotNull('oee')
            ->values();

        $kpis = $this->buildKpis($declarations);
        $machineCards = $this->buildMachineCards($declarations);
        $dailyTrend = $this->buildDailyTrend($declarations);
        $shiftSummary = $this->buildShiftSummary($declarations);
        $ranking = $this->buildRanking($machineCards);
        $missingMachines = $this->buildMissingMachines(
            $allDeclarations,
            $filters
        );

        return view('dashboard', [
            'filters' => $filters,
            'kpis' => $kpis,
            'machineCards' => $machineCards,
            'dailyTrend' => $dailyTrend,
            'shiftSummary' => $shiftSummary,
            'ranking' => $ranking,
            'missingMachines' => $missingMachines,
            'plants' => Plant::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'lines' => ProductionLine::query()
                ->where('is_active', true)
                ->when(
                    $filters['plant_id'],
                    fn (Builder $query) => $query->where(
                        'plant_id',
                        $filters['plant_id']
                    )
                )
                ->orderBy('name')
                ->get(['id', 'plant_id', 'code', 'name']),
            'machines' => Machine::query()
                ->where('is_active', true)
                ->when(
                    $filters['production_line_id'],
                    fn (Builder $query) => $query->where(
                        'production_line_id',
                        $filters['production_line_id']
                    )
                )
                ->orderBy('code')
                ->get(['id', 'production_line_id', 'code', 'name']),
            'shifts' => ProductionShift::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'code', 'name']),
            'targetOee' => 85.0,
            'lastUpdatedAt' => now(),
        ]);
    }

    public function machine(
        Request $request,
        Machine $machine
    ): View {
        $filters = $this->filters($request);

        $query = ShiftProductionDeclaration::query()
            ->with([
                'shift',
                'items.product',
                'operator',
                'approver',
            ])
            ->where('machine_id', $machine->id)
            ->whereBetween('production_date', [
                $filters['date_from'],
                $filters['date_to'],
            ]);

        if ($filters['production_shift_id']) {
            $query->where(
                'production_shift_id',
                $filters['production_shift_id']
            );
        }

        $declarations = $query
            ->orderBy('production_date')
            ->orderBy('production_shift_id')
            ->orderBy('id')
            ->get();

        $calculated = $declarations
            ->whereNotNull('oee')
            ->values();

        $kpis = $this->buildKpis($calculated);
        $dailyTrend = $this->buildDailyTrend($calculated);
        $shiftSummary = $this->buildShiftSummary($calculated);
        $declarationRows = $declarations
            ->sortByDesc(fn (ShiftProductionDeclaration $row) => sprintf(
                '%s-%010d',
                $row->production_date?->format('Y-m-d') ?? '',
                $row->id
            ))
            ->values()
            ->map(fn (ShiftProductionDeclaration $row): array => [
                'declaration' => $row,
                'data_status' => $this->declarationDataStatus($row),
                'products' => $row->items
                    ->map(fn ($item) => trim(
                        ($item->product_code_snapshot ?: '')
                        . ' '
                        . ($item->product_name_snapshot ?: '')
                    ))
                    ->filter()
                    ->implode('; '),
                'total_kg' => (float) $row->items->sum('converted_total_kg'),
                'scrap_kg' => (float) $row->items->sum('converted_scrap_kg'),
                'stable_rate' => $this->estimatedActualRate($row),
                'standard_rate' => $this->weightedStandardRate($row->items),
            ]);

        return view('dashboard-oee-machine', [
            'machine' => $machine->load('productionLine.plant'),
            'filters' => $filters,
            'kpis' => $kpis,
            'dailyTrend' => $dailyTrend,
            'shiftSummary' => $shiftSummary,
            'declarationRows' => $declarationRows,
            'shifts' => ProductionShift::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'code', 'name']),
            'targetOee' => 85.0,
            'lastUpdatedAt' => now(),
        ]);
    }

    private function filters(Request $request): array
    {
        $latestDate = ShiftProductionDeclaration::query()
            ->whereNotNull('oee')
            ->max('production_date');

        $defaultDate = $latestDate
            ? Carbon::parse($latestDate)->toDateString()
            : now()->toDateString();

        $dateFrom = $request->date('date_from')
            ?->toDateString() ?? $defaultDate;

        $dateTo = $request->date('date_to')
            ?->toDateString() ?? $dateFrom;

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'plant_id' => $request->integer('plant_id') ?: null,
            'production_line_id' => $request->integer('production_line_id') ?: null,
            'machine_id' => $request->integer('machine_id') ?: null,
            'production_shift_id' => $request->integer(
                'production_shift_id'
            ) ?: null,
        ];
    }

    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        if ($filters['machine_id']) {
            $query->where('machine_id', $filters['machine_id']);
        }

        if ($filters['production_shift_id']) {
            $query->where(
                'production_shift_id',
                $filters['production_shift_id']
            );
        }

        if ($filters['production_line_id']) {
            $query->whereHas(
                'machine',
                fn (Builder $machineQuery) => $machineQuery->where(
                    'production_line_id',
                    $filters['production_line_id']
                )
            );
        }

        if ($filters['plant_id']) {
            $query->whereHas(
                'machine.productionLine',
                fn (Builder $lineQuery) => $lineQuery->where(
                    'plant_id',
                    $filters['plant_id']
                )
            );
        }
    }

    private function buildKpis(Collection $declarations): array
    {
        return [
            'oee' => $this->averagePercent(
                $declarations->whereNotNull('oee'),
                'oee'
            ),
            'availability' => $this->averagePercent(
                $declarations->whereNotNull('availability'),
                'availability'
            ),
            'performance' => $this->averagePercent(
                $declarations->whereNotNull('performance'),
                'performance'
            ),
            'quality' => $this->averagePercent(
                $declarations->whereNotNull('quality_rate'),
                'quality_rate'
            ),
            'declaration_count' => $declarations->count(),
            'machine_count' => $declarations
                ->pluck('machine_id')
                ->unique()
                ->count(),
            'total_quantity' => (float) $declarations->sum(
                'total_quantity'
            ),
            'total_good' => (float) $declarations->sum(
                'good_quantity'
            ),
            'total_reject' => (float) $declarations->sum(
                'reject_quantity'
            ),
            'total_kg' => (float) $declarations->sum(
                fn (ShiftProductionDeclaration $declaration) =>
                    $declaration->items->sum('converted_total_kg')
            ),
            'scrap_kg' => (float) $declarations->sum(
                fn (ShiftProductionDeclaration $declaration) =>
                    $declaration->items->sum('converted_scrap_kg')
            ),
        ];
    }

    private function buildMachineCards(
        Collection $declarations
    ): Collection {
        return $declarations
            ->groupBy('machine_id')
            ->map(function (Collection $rows): array {
                $latest = $rows
                    ->sortByDesc(
                        fn (ShiftProductionDeclaration $row) =>
                            sprintf(
                                '%s-%010d',
                                $row->production_date?->format(
                                    'Y-m-d'
                                ) ?? '',
                                $row->id
                            )
                    )
                    ->first();

                $items = $latest->items;
                $weightedStandard = $this->weightedStandardRate(
                    $items
                );

                return [
                    'machine' => $latest->machine,
                    'latest' => $latest,
                    'oee' => $this->averagePercent($rows, 'oee'),
                    'availability' => $this->averagePercent(
                        $rows,
                        'availability'
                    ),
                    'performance' => $this->averagePercent(
                        $rows,
                        'performance'
                    ),
                    'quality' => $this->averagePercent(
                        $rows,
                        'quality_rate'
                    ),
                    'declaration_count' => $rows->count(),
                    'total_kg' => (float) $rows->sum(
                        fn (ShiftProductionDeclaration $declaration) =>
                            $declaration->items->sum(
                                'converted_total_kg'
                            )
                    ),
                    'products' => $items
                        ->pluck('product_code_snapshot')
                        ->filter()
                        ->unique()
                        ->values()
                        ->implode(', '),
                    'weighted_standard_rate' => $weightedStandard,
                    'actual_stable_rate' => (
                        $latest->performance !== null
                        && $weightedStandard !== null
                    )
                        ? (float) $latest->performance
                            * $weightedStandard
                        : null,
                ];
            })
            ->sortByDesc('oee')
            ->values();
    }

    private function buildDailyTrend(
        Collection $declarations
    ): Collection {
        return $declarations
            ->groupBy(
                fn (ShiftProductionDeclaration $row) =>
                    $row->production_date?->format('Y-m-d')
            )
            ->map(function (
                Collection $rows,
                string $date
            ): array {
                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d/m'),
                    'oee' => $this->averagePercent(
                        $rows,
                        'oee'
                    ),
                    'availability' => $this->averagePercent(
                        $rows,
                        'availability'
                    ),
                    'performance' => $this->averagePercent(
                        $rows,
                        'performance'
                    ),
                    'quality' => $this->averagePercent(
                        $rows,
                        'quality_rate'
                    ),
                ];
            })
            ->sortBy('date')
            ->values();
    }

    private function buildShiftSummary(
        Collection $declarations
    ): Collection {
        return $declarations
            ->groupBy('production_shift_id')
            ->map(function (Collection $rows): array {
                $shift = $rows->first()->shift;

                return [
                    'shift' => $shift,
                    'oee' => $this->averagePercent(
                        $rows,
                        'oee'
                    ),
                    'availability' => $this->averagePercent(
                        $rows,
                        'availability'
                    ),
                    'performance' => $this->averagePercent(
                        $rows,
                        'performance'
                    ),
                    'quality' => $this->averagePercent(
                        $rows,
                        'quality_rate'
                    ),
                    'count' => $rows->count(),
                ];
            })
            ->sortBy(
                fn (array $row) =>
                    $row['shift']?->display_order ?? 999
            )
            ->values();
    }

    private function buildRanking(
        Collection $machineCards
    ): array {
        return [
            'best' => $machineCards->take(3)->values(),
            'worst' => $machineCards
                ->sortBy('oee')
                ->take(3)
                ->values(),
        ];
    }

    private function buildMissingMachines(
        Collection $allDeclarations,
        array $filters
    ): Collection {
        $machinesQuery = Machine::query()
            ->with('productionLine.plant')
            ->where('is_active', true)
            ->orderBy('code');

        if ($filters['machine_id']) {
            $machinesQuery->whereKey($filters['machine_id']);
        }

        if ($filters['production_line_id']) {
            $machinesQuery->where(
                'production_line_id',
                $filters['production_line_id']
            );
        }

        if ($filters['plant_id']) {
            $machinesQuery->whereHas(
                'productionLine',
                fn (Builder $query) => $query->where(
                    'plant_id',
                    $filters['plant_id']
                )
            );
        }

        return $machinesQuery
            ->get()
            ->map(function (Machine $machine) use (
                $allDeclarations
            ): ?array {
                $rows = $allDeclarations
                    ->where('machine_id', $machine->id)
                    ->values();

                if ($rows->isEmpty()) {
                    return [
                        'machine' => $machine,
                        'declaration' => null,
                        'status' => [
                            'code' => 'NO_DECLARATION',
                            'label' => 'Chưa có phiếu sản lượng',
                            'class' => 'danger',
                        ],
                    ];
                }

                if ($rows->contains(
                    fn (ShiftProductionDeclaration $row) =>
                        $row->oee !== null
                )) {
                    return null;
                }

                $latest = $rows
                    ->sortByDesc('id')
                    ->first();

                return [
                    'machine' => $machine,
                    'declaration' => $latest,
                    'status' => $this->declarationDataStatus(
                        $latest
                    ),
                ];
            })
            ->filter()
            ->values();
    }

    private function declarationDataStatus(
        ShiftProductionDeclaration $declaration
    ): array {
        if ($declaration->oee !== null) {
            return [
                'code' => 'READY',
                'label' => 'Đủ dữ liệu OEE',
                'class' => 'success',
            ];
        }

        $note = mb_strtolower(
            (string) $declaration->calculation_note
        );

        if (
            str_contains($note, 'md488')
            && (
                str_contains($note, 'không có dữ liệu')
                || str_contains($note, 'chưa có giá trị')
                || str_contains($note, 'không tìm thấy tag')
            )
        ) {
            return [
                'code' => 'MISSING_MD488',
                'label' => 'Thiếu dữ liệu MD488',
                'class' => 'danger',
            ];
        }

        if (
            str_contains($note, 'md264')
            && (
                str_contains($note, 'không có dữ liệu')
                || str_contains($note, 'chưa có giá trị')
                || str_contains($note, 'không có khoảng ổn định')
                || str_contains($note, 'không tìm thấy tag')
            )
        ) {
            return [
                'code' => 'MISSING_MD264',
                'label' => 'Thiếu dữ liệu MD264 ổn định',
                'class' => 'warning',
            ];
        }

        if (
            str_contains($note, 'định mức')
            || str_contains($note, 'standard rate')
        ) {
            return [
                'code' => 'MISSING_STANDARD',
                'label' => 'Thiếu định mức sản phẩm',
                'class' => 'warning',
            ];
        }

        if (
            (float) $declaration->total_quantity <= 0
            || $declaration->items->isEmpty()
        ) {
            return [
                'code' => 'MISSING_PRODUCTION',
                'label' => 'Thiếu dữ liệu sản lượng',
                'class' => 'danger',
            ];
        }

        return [
            'code' => 'NOT_CALCULATED',
            'label' => 'Chưa tính lại OEE',
            'class' => 'secondary',
        ];
    }

    private function estimatedActualRate(
        ShiftProductionDeclaration $declaration
    ): ?float {
        $standard = $this->weightedStandardRate(
            $declaration->items
        );

        if (
            $standard === null
            || $declaration->performance === null
        ) {
            return null;
        }

        return (float) $declaration->performance
            * $standard;
    }

    private function weightedStandardRate(
        Collection $items
    ): ?float {
        $valid = $items->filter(
            fn ($item) =>
                (float) $item->standard_rate_snapshot > 0
                && (float) $item->converted_total_kg > 0
                && str_contains(
                    mb_strtolower(
                        (string) $item->rate_unit_snapshot
                    ),
                    'kg'
                )
        );

        $weight = (float) $valid->sum(
            'converted_total_kg'
        );

        if ($weight <= 0) {
            return null;
        }

        return (float) $valid->sum(
            fn ($item) =>
                (float) $item->standard_rate_snapshot
                * (float) $item->converted_total_kg
        ) / $weight;
    }

    private function averagePercent(
        Collection $rows,
        string $column
    ): ?float {
        $values = $rows
            ->pluck($column)
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        return $values->isEmpty()
            ? null
            : round((float) $values->avg() * 100, 2);
    }
}
