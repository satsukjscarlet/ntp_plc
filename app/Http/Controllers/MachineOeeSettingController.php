<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineOeeSetting;
use App\Models\PlcTag;
use App\Models\PlcTagValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MachineOeeSettingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('plc-tags.configure-oee'), 403);

        $machines = Machine::query()
            ->with([
                'productionLine',
                'oeeSetting.availabilityTag',
                'oeeSetting.performanceTag',
            ])
            ->orderBy('code')
            ->get();

        return view('oee-settings.index', compact('machines'));
    }

    public function edit(Request $request, Machine $machine): View
    {
        abort_unless($request->user()->can('plc-tags.configure-oee'), 403);

        $setting = MachineOeeSetting::query()
            ->firstOrNew(['machine_id' => $machine->id]);

        $plcIds = $machine->plcs()->pluck('id');

        $tags = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->orderByRaw("COALESCE(NULLIF(display_name, ''), source_name)")
            ->get();

        if (!$setting->availability_tag_id) {
            $setting->availability_tag_id = $tags
                ->firstWhere('address', '%MD488')
                ?->id;
        }

        if (!$setting->performance_tag_id) {
            $setting->performance_tag_id = $tags
                ->firstWhere('address', '%MD264')
                ?->id;
        }

        $availabilityStats = $this->recentStats($setting->availability_tag_id);
        $performanceStats = $this->recentStats($setting->performance_tag_id);

        return view('oee-settings.edit', compact(
            'machine',
            'setting',
            'tags',
            'availabilityStats',
            'performanceStats'
        ));
    }

    public function update(
        Request $request,
        Machine $machine
    ): RedirectResponse {
        abort_unless($request->user()->can('plc-tags.configure-oee'), 403);

        $data = $request->validate([
            'availability_tag_id' => ['required', 'integer', 'exists:plc_tags,id'],
            'availability_value_unit' => ['required', 'in:hours,minutes,seconds'],
            'performance_tag_id' => ['required', 'integer', 'exists:plc_tags,id'],
            'performance_aggregation' => ['required', 'in:stable_average,average,last,maximum'],
            'performance_stability_tolerance' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'performance_stability_min_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'performance_min_valid_value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plcIds = $machine->plcs()->pluck('id');

        foreach ([
            'availability_tag_id' => 'Tag MD488',
            'performance_tag_id' => 'Tag MD264',
        ] as $field => $label) {
            $belongs = PlcTag::query()
                ->whereKey($data[$field])
                ->whereIn('plc_id', $plcIds)
                ->exists();

            if (!$belongs) {
                return back()
                    ->withInput()
                    ->with('error', $label . ' không thuộc PLC của máy ' . $machine->code . '.');
            }
        }

        $data['is_active'] = $request->boolean('is_active');

        MachineOeeSetting::query()->updateOrCreate(
            ['machine_id' => $machine->id],
            $data
        );

        return redirect()
            ->route('oee-settings.index')
            ->with('success', 'Đã lưu cấu hình MD488/MD264 cho máy ' . $machine->code . '.');
    }

    private function recentStats(?int $tagId): ?array
    {
        if (!$tagId) {
            return null;
        }

        $rows = PlcTagValue::query()
            ->where('plc_tag_id', $tagId)
            ->where('collected_at', '>=', now()->subDay())
            ->orderBy('collected_at')
            ->get(['numeric_value', 'raw_value', 'collected_at'])
            ->map(function (PlcTagValue $row): ?array {
                $value = $this->numericValue($row);

                return $value === null
                    ? null
                    : ['value' => $value, 'collected_at' => $row->collected_at];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'count' => $rows->count(),
            'min' => $rows->min('value'),
            'max' => $rows->max('value'),
            'avg' => $rows->avg('value'),
            'first_at' => $rows->first()['collected_at'],
            'last_at' => $rows->last()['collected_at'],
        ];
    }

    private function numericValue(PlcTagValue $row): ?float
    {
        if ($row->numeric_value !== null && is_numeric($row->numeric_value)) {
            return (float) $row->numeric_value;
        }

        $raw = trim((string) $row->raw_value);

        return $raw !== '' && is_numeric($raw)
            ? (float) $raw
            : null;
    }
}
