<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\WhatsappSession;
use App\Services\Waha\WahaClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function show(WahaClient $waha)
    {
        $session = WhatsappSession::query()->first();
        $remote = null;
        $remoteError = null;

        if (! $session) {
            return view('onboarding', [
                'step' => 'create',
                'session' => null,
                'remote' => null,
                'remoteError' => null,
            ]);
        }

        try {
            $remote = $this->ensureRemoteSessionStarted($waha);
        } catch (\Throwable $exception) {
            $remoteError = $exception->getMessage();
        }

        if (($remote['status'] ?? null) === 'WORKING') {
            $this->markSessionAsConnected($session, $remote);

            return redirect()->route('dashboard');
        }

        return view('onboarding', [
            'step' => 'qr',
            'session' => $session,
            'remote' => $remote,
            'remoteError' => $remoteError,
        ]);
    }

    public function store(Request $request, WahaClient $waha)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:whatsapp_sessions,name'],
        ]);

        WhatsappSession::create([
            'name' => $data['name'],
            'status' => SessionStatus::QrPending,
            'provider' => 'waha',
            'metadata' => [
                'waha_session' => config('waha.session', 'default'),
            ],
        ]);

        $this->createRemoteSessionIfMissing($waha);
        $this->ensureRemoteSessionStarted($waha);

        return redirect()->route('onboarding.show');
    }

    public function qr(WahaClient $waha)
    {
        $this->ensureRemoteSessionStarted($waha);

        $response = $waha->qrImage();

        return response($response->body(), 200)
            ->header('Content-Type', $response->header('Content-Type') ?: 'image/png')
            ->header('Cache-Control', 'no-store');
    }

    public function status(WahaClient $waha): JsonResponse
    {
        $session = WhatsappSession::query()->first();

        if (! $session) {
            return response()->json([
                'connected' => false,
                'status' => null,
                'redirect' => route('onboarding.show'),
            ]);
        }

        try {
            $remote = $waha->status();
        } catch (\Throwable $exception) {
            return response()->json([
                'connected' => false,
                'status' => null,
                'error' => $exception->getMessage(),
            ], 503);
        }

        if (($remote['status'] ?? null) === 'WORKING') {
            $this->markSessionAsConnected($session, $remote);

            return response()->json([
                'connected' => true,
                'status' => 'WORKING',
                'redirect' => route('dashboard'),
            ]);
        }

        return response()->json([
            'connected' => false,
            'status' => $remote['status'] ?? null,
        ]);
    }

    public function refreshQr(WahaClient $waha)
    {
        $this->createRemoteSessionIfMissing($waha);

        try {
            $waha->restart();
        } catch (\Throwable $exception) {
            $waha->start();
        }

        return redirect()->route('onboarding.show');
    }

    private function createRemoteSessionIfMissing(WahaClient $waha): void
    {
        try {
            $waha->createSession();
        } catch (RequestException $exception) {
            if (! $this->remoteSessionAlreadyExists($exception)) {
                throw $exception;
            }
        }
    }

    private function remoteSessionAlreadyExists(RequestException $exception): bool
    {
        $response = $exception->response;
        $message = (string) ($response?->json('message') ?? '');

        return in_array($response?->status(), [409, 422], true)
            && str_contains($message, 'already exists');
    }

    private function ensureRemoteSessionStarted(WahaClient $waha): ?array
    {
        try {
            $remote = $waha->status();
        } catch (RequestException $exception) {
            if ($exception->response?->status() !== 404) {
                throw $exception;
            }

            $this->createRemoteSessionIfMissing($waha);
            $remote = null;
        }

        $status = $remote['status'] ?? null;

        if (in_array($status, ['STARTING', 'SCAN_QR_CODE', 'WORKING'], true)) {
            return $remote;
        }

        try {
            $waha->start();
        } catch (RequestException $exception) {
            if ($exception->response?->status() !== 404) {
                throw $exception;
            }

            $this->createRemoteSessionIfMissing($waha);
            $waha->start();
        }

        try {
            return $waha->status();
        } catch (\Throwable) {
            return $remote;
        }
    }

    private function markSessionAsConnected(WhatsappSession $session, array $remote): void
    {
        $session->update([
            'status' => SessionStatus::Connected,
            'last_connected_at' => now(),
            'metadata' => array_merge($session->metadata ?? [], [
                'waha_session' => config('waha.session', 'default'),
                'remote' => $remote,
            ]),
        ]);
    }
}
