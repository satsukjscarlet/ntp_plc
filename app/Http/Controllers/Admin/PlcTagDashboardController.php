<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plc;
use App\Models\PlcTag;
use Illuminate\View\View;

class PlcTagDashboardController extends Controller
{
    public function __invoke(Plc $plc): View
    {
        $stats = [
            'total' => PlcTag::where('plc_id', $plc->id)->count(),
            'active' => PlcTag::where('plc_id', $plc->id)
                ->where('is_active', true)
                ->count(),
            'new' => PlcTag::where('plc_id', $plc->id)
                ->where('sync_status', 'NEW')
                ->count(),
            'renamed' => PlcTag::where('plc_id', $plc->id)
                ->where('sync_status', 'RENAMED')
                ->count(),
            'removed' => PlcTag::where('plc_id', $plc->id)
                ->where('sync_status', 'REMOVED')
                ->count(),
        ];

        $tags = PlcTag::query()
            ->where('plc_id', $plc->id)
            ->orderByDesc('is_active')
            ->orderBy('display_order')
            ->orderBy('display_name')
            ->paginate(100);

        return view('admin.plcs.tags.sync-dashboard', compact(
            'plc',
            'stats',
            'tags'
        ));
    }
}
