<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class AuditNamedRoutes extends Command
{
    protected $signature = 'routes:audit-views';

    protected $description = 'Kiểm tra các route() được gọi trong Blade nhưng chưa được khai báo.';

    public function handle(): int
    {
        $defined = collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->flip();

        $used = collect();
        $root = resource_path('views');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root)
        );

        $files = new RegexIterator(
            $iterator,
            '/^.+\.blade\.php$/i',
            RegexIterator::GET_MATCH
        );

        foreach ($files as $matches) {
            $file = $matches[0];
            $contents = file_get_contents($file);

            preg_match_all(
                "/route\(\s*['\\\"]([^'\\\"]+)['\\\"]/",
                $contents,
                $routeMatches
            );

            foreach ($routeMatches[1] ?? [] as $routeName) {
                // Bỏ qua các chuỗi không phải tên route thực tế.
                if (in_array($routeName, ['machineCode'], true)) {
                    continue;
                }

                $used->push([
                    'route' => $routeName,
                    'file' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file),
                ]);
            }
        }

        $missing = $used
            ->filter(fn (array $row) => !$defined->has($row['route']))
            ->groupBy('route');

        if ($missing->isEmpty()) {
            $this->info('OK: Tất cả route() trong Blade đều đã được khai báo.');
            return self::SUCCESS;
        }

        $this->error('Phát hiện route chưa được khai báo:');

        $rows = $missing->map(
            fn ($items, $name) => [
                $name,
                $items->pluck('file')->unique()->implode(', '),
            ]
        )->values()->all();

        $this->table(['Route thiếu', 'File đang gọi'], $rows);

        return self::FAILURE;
    }
}
