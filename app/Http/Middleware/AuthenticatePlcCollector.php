<?php

namespace App\Http\Middleware;

use App\Models\Plc;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePlcCollector
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) $request->bearerToken());

        if ($token === '') {
            return new JsonResponse([
                'message' => 'Thiếu Bearer token.',
            ], 401);
        }

        $plc = Plc::query()
            ->where('collector_token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->first();

        if (!$plc) {
            return new JsonResponse([
                'message' => 'Collector token không hợp lệ.',
            ], 401);
        }

        $request->attributes->set('collector_plc', $plc);

        return $next($request);
    }
}
