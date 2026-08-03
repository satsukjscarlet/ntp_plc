<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Collector\HeartbeatRequest;
use App\Http\Requests\Api\Collector\StorePlcDataRequest;
use App\Models\Plc;
use App\Services\Collector\CollectorDataService;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class PlcDataController extends Controller
{
    public function __construct(
        private readonly CollectorDataService $collectorDataService
    ) {
    }

    public function store(
        StorePlcDataRequest $request
    ): JsonResponse {
        try {
            $plc = $this->resolveAuthenticatedPlc($request);

            $result = $this->collectorDataService->store(
                $plc,
                $request->validated()
            );

            return response()->json([
                'message' => 'Đã nhận dữ liệu PLC.',
                'data' => $result,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Không thể lưu dữ liệu PLC.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function heartbeat(
        HeartbeatRequest $request
    ): JsonResponse {
        try {
            $plc = $this->resolveAuthenticatedPlc($request);

            $result = $this->collectorDataService->heartbeat(
                $plc,
                $request->validated()
            );

            return response()->json([
                'message' => 'Heartbeat thành công.',
                'data' => $result,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Không thể cập nhật heartbeat.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function resolveAuthenticatedPlc(
        object $request
    ): Plc {
        /*
         * Middleware collector nên gắn PLC vào request:
         *
         * $request->attributes->set('collector_plc', $plc);
         */
        $plc = $request->attributes->get('collector_plc');

        if ($plc instanceof Plc) {
            return $plc;
        }

        /*
         * Tương thích middleware cũ có thể gắn thuộc tính plc.
         */
        $plc = $request->attributes->get('plc');

        if ($plc instanceof Plc) {
            return $plc;
        }

        /*
         * Phương án dự phòng theo plc_code.
         * Token middleware vẫn phải chạy trước controller.
         */
        $plcCode = $request->input('plc_code');

        if ($plcCode) {
            $plc = Plc::query()
                ->where('code', $plcCode)
                ->first();

            if ($plc) {
                return $plc;
            }
        }

        throw new RuntimeException(
            'Không xác định được PLC từ collector token.'
        );
    }
}
