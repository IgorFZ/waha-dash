<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\WhatsappSession;
use App\Services\Waha\WahaClient;

class DashboardController extends Controller
{
    public function __invoke(WahaClient $waha)
    {
        $session = WhatsappSession::query()->first();

        if (! $session) {
            return redirect()->route('onboarding.show');
        }

        try {
            $remote = $waha->status();
        } catch (\Throwable $exception) {
            return redirect()->route('onboarding.show');
        }

        if (($remote['status'] ?? null) !== 'WORKING') {
            $session->update([
                'status' => SessionStatus::QrPending,
            ]);

            return redirect()->route('onboarding.show');
        }

        if (! $session->isConnected()) {
            $session->update([
                'status' => SessionStatus::Connected,
                'last_connected_at' => now(),
                'metadata' => array_merge($session->metadata ?? [], [
                    'waha_session' => config('waha.session', 'default'),
                    'remote' => $remote,
                ]),
            ]);
        }

        return view('dashboard', [
            'session' => $session->fresh(),
            'remote' => $remote,
        ]);


        
    }
}
