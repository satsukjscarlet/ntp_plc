<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MonitoringService;
use Illuminate\Http\JsonResponse;

class MonitoringApiController extends Controller
{
    public function __construct(
        private readonly MonitoringService $monitoringService
    ) {
    }

    public function show(string $machineCode): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->monitoringService->getMachineSnapshot($machineCode),
        ]);
    }
}
