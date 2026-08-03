<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\ShiftProductionDeclaration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OeeReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = ShiftProductionDeclaration::query()
            ->with(['machine', 'shift'])
            ->whereNotNull('oee')
            ->latest('production_date')
            ->latest('id');

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->integer('machine_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('production_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('production_date', '<=', $request->date('date_to'));
        }

        $rows = $query->get();
        $daily = $rows->groupBy(fn ($row) => $row->machine_id . '|' . $row->production_date->format('Y-m-d'))
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'machine' => $first->machine,
                    'date' => $first->production_date,
                    'shift_count' => $group->count(),
                    'availability' => $group->avg(fn ($row) => (float) $row->availability),
                    'performance' => $group->avg(fn ($row) => (float) $row->performance),
                    'quality' => $group->avg(fn ($row) => (float) $row->quality_rate),
                    'oee' => $group->avg(fn ($row) => (float) $row->oee),
                ];
            })
            ->sortByDesc(fn ($row) => $row['date']->format('Y-m-d'))
            ->values();

        return view('oee.index', [
            'rows' => $rows,
            'daily' => $daily,
            'machines' => Machine::query()->where('is_active', true)->orderBy('code')->get(),
        ]);
    }
}
