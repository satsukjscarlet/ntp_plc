<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = collect();

        if (Schema::hasTable('activity_log')) {
            $logs = DB::table('activity_log')
                ->orderByDesc('id')
                ->paginate(30);
        }

        return view('activity_logs.index', compact('logs'));
    }
}
