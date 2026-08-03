<?php

namespace App\Http\Controllers;

use App\Models\Plc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PlcCollectorTokenController extends Controller
{
    public function regenerate(Plc $plc): RedirectResponse
    {
        abort_unless(
            auth()->user()?->can('plcs.update'),
            403
        );

        $plainToken = Str::random(64);

        $plc->update([
            'collector_token_hash' =>
                hash('sha256', $plainToken),
        ]);

        return back()
            ->with('success', 'Đã tạo collector token mới.')
            ->with('collector_token', $plainToken);
    }
}
