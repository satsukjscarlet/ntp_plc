<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plc;
use App\Services\Collector\PlcPortalTagParser;
use App\Services\Collector\TagSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Throwable;

class PlcTagSyncController extends Controller
{
    public function __invoke(
        Plc $plc,
        PlcPortalTagParser $parser,
        TagSynchronizationService $service
    ): RedirectResponse {
        if (!$plc->collector_url) {
            return back()->with('error', 'PLC chưa có collector_url.');
        }

        try {
            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->get($plc->collector_url);

            $response->throw();

            $tags = $parser->parse($response->body());

            if ($tags === []) {
                return back()->with(
                    'error',
                    'Không tìm thấy tag trong PLC Portal.'
                );
            }

            $result = $service->synchronize($plc, $tags);

            return back()->with(
                'success',
                sprintf(
                    'Đồng bộ thành công: %d mới, %d cập nhật, %d khớp, %d ngừng sử dụng.',
                    $result['new'],
                    $result['updated'],
                    $result['matched'],
                    $result['removed']
                )
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Đồng bộ thất bại: ' . $e->getMessage()
            );
        }
    }
}
