<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function show(string $machineCode): View
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        if (strtoupper($machine->code) === 'M62') {
            return view('monitoring.m62-technology', compact('machine'));
        }

        return view('monitoring.show', compact('machine'));
    }
}
