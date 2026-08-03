<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

class AuditPermissionRoutes extends Command
{
    protected $signature = 'permissions:audit-routes';

    protected $description = 'Kiểm tra các route nghiệp vụ quan trọng có permission middleware hay chưa.';

    /**
     * Các route framework/auth hợp lệ dù không dùng permission nghiệp vụ.
     *
     * @var array<int, string>
     */
    private array $ignoredRouteNames = [
        'login',
        'logout',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
        'password.confirm',
        'password.update',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'sanctum.csrf-cookie',
        'storage.local',
        'storage.local.upload',
    ];

    /**
     * @var array<int, string>
     */
    private array $ignoredUriPrefixes = [
        'login',
        'logout',
        'forgot-password',
        'reset-password',
        'password',
        'profile',
        'verify-email',
        'email/verification-notification',
        'confirm-password',
        'sanctum',
        'storage',
        'up',
        'api/collector',
    ];

    public function handle(): int
    {
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if ($this->isIgnored($route, $uri)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            /*
             * Laravel có thể trả alias:
             *   permission:users.view
             *
             * hoặc class đã resolve:
             *   Spatie\Permission\Middleware\PermissionMiddleware:users.view
             */
            $hasPermission = collect($middleware)->contains(
                fn (string $item): bool =>
                    str_starts_with($item, 'permission:')
                    || str_starts_with($item, 'role:')
                    || str_starts_with($item, 'role_or_permission:')
                    || str_contains(
                        $item,
                        'Spatie\\Permission\\Middleware\\PermissionMiddleware:'
                    )
                    || str_contains(
                        $item,
                        'Spatie\\Permission\\Middleware\\RoleMiddleware:'
                    )
                    || str_contains(
                        $item,
                        'Spatie\\Permission\\Middleware\\RoleOrPermissionMiddleware:'
                    )
            );

            if (!$hasPermission) {
                $missing[] = [
                    implode('|', $route->methods()),
                    $uri,
                    $route->getName() ?: '—',
                    implode(', ', $middleware),
                ];
            }
        }

        if ($missing === []) {
            $this->info(
                'OK: Không phát hiện route nghiệp vụ thiếu permission middleware.'
            );

            return self::SUCCESS;
        }

        $this->warn(
            'Các route dưới đây chưa có permission middleware. '
            . 'Hãy xác nhận đây là route công khai hoặc bổ sung quyền:'
        );

        $this->table(
            ['Method', 'URI', 'Name', 'Middleware'],
            $missing
        );

        return self::FAILURE;
    }

    private function isIgnored(
        LaravelRoute $route,
        string $uri
    ): bool {
        if ($uri === '/') {
            return true;
        }

        if (
            $route->getName()
            && in_array(
                $route->getName(),
                $this->ignoredRouteNames,
                true
            )
        ) {
            return true;
        }

        if (str_starts_with($uri, 'api/monitoring/')) {
            /*
             * API Monitoring cũ chưa khóa trong đợt này để tránh
             * làm hỏng frontend đang sử dụng.
             */
            return true;
        }

        foreach ($this->ignoredUriPrefixes as $prefix) {
            if (
                $uri === $prefix
                || str_starts_with($uri, $prefix . '/')
            ) {
                return true;
            }
        }

        return false;
    }
}
