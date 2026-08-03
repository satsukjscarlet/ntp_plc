<?php

namespace App\Providers;

use App\Models\Machine;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $sidebarMachines = collect();

            try {
                if (Schema::hasTable('machines')) {
                    $sidebarMachines = Machine::query()
                        ->where('is_active', true)
                        ->orderBy('code')
                        ->get(['id', 'code', 'name']);
                }
            } catch (Throwable) {
                // Không làm hỏng giao diện nếu database chưa sẵn sàng khi migrate/deploy.
            }

            $view->with('sidebarMachines', $sidebarMachines);
        });
    }
}
