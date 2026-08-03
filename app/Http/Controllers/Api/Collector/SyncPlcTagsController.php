<?php

namespace App\Http\Controllers\Api\Collector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collector\SyncPlcTagsRequest;
use App\Models\Plc;
use App\Services\Collector\TagSynchronizationService;
use Illuminate\Http\JsonResponse;

class SyncPlcTagsController extends Controller
{
    public function __invoke(
        SyncPlcTagsRequest $request,
        TagSynchronizationService $service
    ): JsonResponse {
        /** @var Plc|null $plc */
        $plc = $request->attributes->get('collector_plc');

        if (!$plc) {
            return response()->json([
                'message' => 'Không xác định được PLC collector.',
            ], 401);
        }

        $result = $service->synchronize(
            $plc,
            $request->validated('tags')
        );

        return response()->json([
            'message' => 'Đồng bộ tag PLC thành công.',
            'plc' => [
                'id' => $plc->id,
                'name' => $plc->name,
                'machine_id' => $plc->machine_id,
            ],
            'result' => $result,
        ]);
    }
}
