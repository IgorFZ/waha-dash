<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\Contact;
use App\Models\Subscription;
use App\Models\SubscriptionRun;
use App\Models\WhatsappSession;
use App\Services\Waha\WahaClient;
use Illuminate\Support\Facades\DB;

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

        // Collect metrics
        $totalContacts = Contact::where('session_id', $session->id)->count();
        $totalSubscriptions = Subscription::where('session_id', $session->id)->count();
        $totalMessagesSent = SubscriptionRun::whereHas('subscription', fn($q) => $q->where('session_id', $session->id))
            ->count();

        // Get subscription data for last 6 months
        $subscriptionsLast6Months = DB::table('subscriptions')
            ->select(
                DB::raw("DATE_TRUNC('month', created_at) as month"),
                DB::raw('COUNT(*) as count')
            )
            ->where('session_id', $session->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => \Carbon\Carbon::parse($item->month)->format('M/Y'),
                    'count' => (int) $item->count,
                ];
            })
            ->values()
            ->toArray();

        return view('dashboard', [
            'session' => $session->fresh(),
            'remote' => $remote,
            'totalContacts' => $totalContacts,
            'totalSubscriptions' => $totalSubscriptions,
            'totalMessagesSent' => $totalMessagesSent,
            'subscriptionsLast6Months' => $subscriptionsLast6Months,
        ]);
    }
}
