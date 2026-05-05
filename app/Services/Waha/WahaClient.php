<?php

namespace App\Services\Waha;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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

    public function contacts(
        ?string $session = null,
        int $limit = 100,
        int $offset = 0,
        string $sortBy = 'id',
        string $sortOrder = 'asc',
    ): array {
        return $this->request()
            ->get('/api/contacts/all', [
                'session' => $session ?? $this->session(),
                'limit' => $limit,
                'offset' => $offset,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ])
            ->throw()
            ->json();
    }

    public function contact(string $contactId, ?string $session = null): array
    {
        return $this->request()
            ->get('/api/contacts', [
                'session' => $session ?? $this->session(),
                'contactId' => $contactId,
            ])
            ->throw()
            ->json();
    }

    public function checkContactExists(string $phone, ?string $session = null): array
    {
        return $this->request()
            ->get('/api/contacts/check-exists', [
                'session' => $session ?? $this->session(),
                'phone' => $phone,
            ])
            ->throw()
            ->json();
    }

    public function updateContact(string $chatId, array $data, ?string $session = null): array
    {
        $session = $session ?? $this->session();
        $chatId = rawurlencode($chatId);

        return $this->request()
            ->put("/api/{$session}/contacts/{$chatId}", $data)
            ->throw()
            ->json() ?? [];
    }

    public function lids(?string $session = null, int $limit = 100, int $offset = 0): array
    {
        $session = $session ?? $this->session();

        return $this->request()
            ->get("/api/{$session}/lids", [
                'limit' => $limit,
                'offset' => $offset,
            ])
            ->throw()
            ->json();
    }

    public function phoneNumberByLid(string $lid, ?string $session = null): array
    {
        $session = $session ?? $this->session();
        $lid = str_replace('@lid', '', $lid);

        return $this->request()
            ->get("/api/{$session}/lids/{$lid}")
            ->throw()
            ->json();
    }

    public function createSession(?string $session = null): ?array
    {
        $session = $session ?? $this->session();

        return $this->request()
            ->post('/api/sessions', [
                'name' => $session,
                'start' => true,
                'config' => new \stdClass(),
            ])
            ->throw()
            ->json();
    }

    public function start(?string $session = null): ?array
    {
        $session = $session ?? $this->session();

        return $this->request()
            ->post("/api/sessions/{$session}/start")
            ->throw()
            ->json();
    }

    public function restart(?string $session = null): ?array
    {
        $session = $session ?? $this->session();

        return $this->request()
            ->post("/api/sessions/{$session}/restart")
            ->throw()
            ->json();
    }

    public function me(?string $session = null): mixed
    {
        $session = $session ?? $this->session();

        return $this->request()
            ->get("/api/sessions/{$session}/me")
            ->throw()
            ->json();
    }

    public function qrImage(?string $session = null): Response
    {
        $session = $session ?? $this->session();

        return $this->request()
            ->accept('image/png')
            ->get("/api/{$session}/auth/qr?format=image")
            ->throw();
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
