<?php

namespace App\Services;

use App\Models\ProductionShift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ShiftTimeService
{
    public function range(
        ProductionShift $shift,
        Carbon|string $productionDate
    ): array {
        $date = $productionDate instanceof Carbon
            ? $productionDate->copy()
            : Carbon::parse($productionDate);

        $start = Carbon::parse(
            $date->format('Y-m-d') . ' ' . $shift->start_time
        );

        $end = Carbon::parse(
            $date->format('Y-m-d') . ' ' . $shift->end_time
        );

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }
}
