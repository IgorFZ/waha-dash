<?php

namespace App\Services\Contacts;

use App\Models\Contact;
use App\Models\WhatsappSession;
use App\Services\Waha\WahaClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;

class ContactWhatsappSyncService
{
    public function __construct(private readonly WahaClient $waha)
    {
    }

    public function dataForManualContact(WhatsappSession $session, array $data, ?Contact $currentContact = null): array
    {
        $phoneNumber = $this->normalizePhone($data['phone_number']);
        $sessionName = $this->remoteSessionName($session);

        try {
            $exists = $this->waha->checkContactExists($phoneNumber, $sessionName);
        } catch (RequestException $exception) {
            throw ValidationException::withMessages([
                'phone_number' => 'Não foi possível validar este número na WAHA.',
            ]);
        }

        if (($exists['numberExists'] ?? false) !== true || ! isset($exists['chatId'])) {
            throw ValidationException::withMessages([
                'phone_number' => 'Este número não foi encontrado no WhatsApp.',
            ]);
        }

            $chatId = (string) $exists['chatId'];
            $duplicate = Contact::query()
            ->where('session_id', $session->id)
            ->where('chat_id', $chatId)
            ->when($currentContact, fn($query) => $query->whereKeyNot($currentContact->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'phone_number' => 'Já existe um contato com este número no WhatsApp.',
            ]);
        }

        $displayName = trim((string) $data['display_name']);
        try {
            $this->syncAddressBookName($sessionName, $chatId, $displayName);
        } catch (RequestException $exception) {
            throw ValidationException::withMessages([
                'display_name' => 'Não foi possível sincronizar este contato com o WhatsApp.',
            ]);
        }

        return [
            'chat_id' => $chatId,
            'phone_number' => $this->phoneFromChatId($chatId) ?? $phoneNumber,
            'display_name' => $displayName,
            'push_name' => $displayName,
            'notes' => $data['notes'] ?? null,
            'source' => 'manual',
            'synced_at' => now(),
            'raw_payload' => [
                'check_exists' => $exists,
                'manual_sync' => [
                    'display_name' => $displayName,
                ],
            ],
        ];
    }

    private function syncAddressBookName(string $sessionName, string $chatId, string $displayName): void
    {
        $this->waha->updateContact($chatId, [
            'firstName' => $displayName,
        ], $sessionName);
    }

    private function normalizePhone(string $phoneNumber): string
    {
        return preg_replace('/\D+/', '', $phoneNumber) ?? '';
    }

    private function phoneFromChatId(string $chatId): ?string
    {
        return str_ends_with($chatId, '@c.us')
            ? str_replace('@c.us', '', $chatId)
            : null;
    }

    private function remoteSessionName(WhatsappSession $session): string
    {
        return (string) ($session->metadata['waha_session'] ?? config('waha.session', 'default'));
    }
}
