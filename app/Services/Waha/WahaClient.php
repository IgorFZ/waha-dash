<?php

namespace App\Services\Waha;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WahaClient
{
    public function status(?string $session = null): array
    {
        return $this->request()
            ->get('/api/sessions/'.($session ?? $this->session()))
            ->throw()
            ->json();
    }

    public function sendText(string $chatId, string $text, ?string $session = null): array
    {
        return $this->request()
            ->post('/api/sendText', [
                'session' => $session ?? $this->session(),
                'chatId' => $chatId,
                'text' => $text,
            ])
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('waha.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('waha.timeout'));

        if ($apiKey = config('waha.api_key')) {
            $request = $request->withHeader('X-Api-Key', $apiKey);
        }

        return $request;
    }

    private function session(): string
    {
        return (string) config('waha.session', 'default');
    }
}
