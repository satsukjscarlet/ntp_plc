<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlcTagRequest;
use App\Models\Machine;
use App\Models\PlcTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineTagController extends Controller
{
    public function index(Request $request, string $machineCode): View
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $plcIds = $machine->plcs()->pluck('id');

        $query = PlcTag::query()
            ->with('currentValue')
            ->whereIn('plc_id', $plcIds);

        if ($keyword = trim((string) $request->input('keyword'))) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('source_name', 'like', "%{$keyword}%")
                    ->orWhere('display_name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%")
                    ->orWhere('source_key', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('data_type')) {
            $query->where('data_type', $request->string('data_type'));
        }

        if ($request->filled('display_group')) {
            $query->where('display_group', $request->string('display_group'));
        }

        if ($request->filled('visibility')) {
            $query->where(
                'is_visible',
                $request->string('visibility')->toString() === 'visible'
            );
        }

        if ($request->filled('history')) {
            $query->where(
                'history_enabled',
                $request->string('history')->toString() === 'enabled'
            );
        }

        $tags = $query
            ->orderByRaw("CASE WHEN display_group IS NULL OR display_group = '' THEN 1 ELSE 0 END")
            ->orderBy('display_group')
            ->orderBy('display_order')
            ->orderBy('source_name')
            ->paginate(50)
            ->withQueryString();

        $groups = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->whereNotNull('display_group')
            ->where('display_group', '<>', '')
            ->distinct()
            ->orderBy('display_group')
            ->pluck('display_group');

        $dataTypes = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->whereNotNull('data_type')
            ->distinct()
            ->orderBy('data_type')
            ->pluck('data_type');

        return view('machines.tags.index', compact(
            'machine',
            'tags',
            'groups',
            'dataTypes'
        ));
    }

    public function edit(
        string $machineCode,
        PlcTag $tag
    ): JsonResponse {
        $machine = $this->resolveMachineTag($machineCode, $tag);

        return response()->json([
            'success' => true,
            'machine' => [
                'id' => $machine->id,
                'code' => $machine->code,
                'name' => $machine->name,
            ],
            'tag' => [
                'id' => $tag->id,
                'source_name' => $tag->source_name,
                'source_key' => $tag->source_key,
                'address' => $tag->address,
                'data_type' => $tag->data_type,
                'display_name' => $tag->display_name,
                'unit' => $tag->unit,
                'display_group' => $tag->display_group,
                'display_order' => (int) $tag->display_order,
                'decimal_places' => (int) $tag->decimal_places,
                'is_visible' => (bool) $tag->is_visible,
                'history_enabled' => (bool) $tag->history_enabled,
                'history_deadband' => (float) $tag->history_deadband,
                'alarm_low' => $tag->alarm_low,
                'alarm_high' => $tag->alarm_high,
            ],
        ]);
    }

    public function update(
        UpdatePlcTagRequest $request,
        string $machineCode,
        PlcTag $tag
    ): JsonResponse {
        $this->resolveMachineTag($machineCode, $tag);

        $tag->forceFill($request->validated());
        $tag->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật cấu hình tag.',
            'tag' => [
                'id' => $tag->id,
                'display_name' => $tag->display_name,
                'unit' => $tag->unit,
                'display_group' => $tag->display_group,
                'display_order' => (int) $tag->display_order,
                'decimal_places' => (int) $tag->decimal_places,
                'is_visible' => (bool) $tag->is_visible,
                'history_enabled' => (bool) $tag->history_enabled,
                'history_deadband' => (float) $tag->history_deadband,
                'alarm_low' => $tag->alarm_low,
                'alarm_high' => $tag->alarm_high,
            ],
        ]);
    }

    public function bulkUpdate(
        Request $request,
        string $machineCode
    ): JsonResponse {
        $validated = $request->validate([
            'tag_ids' => ['required', 'array', 'min:1'],
            'tag_ids.*' => ['integer'],
            'display_group' => ['nullable', 'string', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],
            'history_enabled' => ['nullable', 'boolean'],
            'history_deadband' => ['nullable', 'numeric', 'min:0'],
        ]);

        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $plcIds = $machine->plcs()->pluck('id');

        $updates = collect([
            'display_group' => $validated['display_group'] ?? null,
            'is_visible' => $validated['is_visible'] ?? null,
            'history_enabled' => $validated['history_enabled'] ?? null,
            'history_deadband' => $validated['history_deadband'] ?? null,
        ])->reject(fn ($value) => $value === null)->all();

        if ($updates === []) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa chọn nội dung cần cập nhật.',
            ], 422);
        }

        $updated = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->whereIn('id', $validated['tag_ids'])
            ->update($updates);

        return response()->json([
            'success' => true,
            'message' => "Đã cập nhật {$updated} tag.",
        ]);
    }

    private function resolveMachineTag(
        string $machineCode,
        PlcTag $tag
    ): Machine {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $belongsToMachine = $machine->plcs()
            ->whereKey($tag->plc_id)
            ->exists();

        abort_unless($belongsToMachine, 404);

        return $machine;
    }
}
