<?php

namespace App\Services\Contacts;

use App\Models\Contact;
use App\Models\WhatsappSession;
use App\Services\Waha\WahaClient;
use Illuminate\Support\Facades\Cache;

class ContactImportService
{
    public function __construct(private readonly WahaClient $waha) {}

    public function preview(WhatsappSession $session, bool $includeUnnamed = false): array
    {
        $snapshot = $this->snapshot($session);
        $existingChatIds = Contact::query()
            ->where('session_id', $session->id)
            ->pluck('chat_id')
            ->flip();

        $contacts = collect($snapshot['contacts'])
            ->when(! $includeUnnamed, fn($contacts) => $contacts->filter(fn(array $contact) => $contact['has_name']))
            ->map(function (array $contact) use ($existingChatIds) {
                $contact['already_imported'] = $existingChatIds->has($contact['chat_id']);
                unset($contact['raw_payload']);

                return $contact;
            })
            ->values()
            ->all();

        return [
            'contacts' => $contacts,
            'meta' => $snapshot['meta'],
        ];
    }

    public function importSelected(WhatsappSession $session, array $chatIds): array
    {
        $contactsByChatId = collect($this->snapshot($session)['contacts'])->keyBy('chat_id');
        $summary = [
            'requested' => count($chatIds),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $selectedContacts = collect(array_unique($chatIds))
            ->map(fn(string $chatId) => $contactsByChatId->get($chatId))
            ->filter(fn(?array $contact) => $contact && $contact['importable'])
            ->values();

        if ($selectedContacts->isNotEmpty()) {
            $now = now();
            $existingChatIds = Contact::query()
                ->where('session_id', $session->id)
                ->whereIn('chat_id', $selectedContacts->pluck('chat_id'))
                ->pluck('chat_id')
                ->flip();

            $rows = $selectedContacts
                ->map(fn(array $contact) => [
                    'session_id' => $session->id,
                    'chat_id' => $contact['chat_id'],
                    'phone_number' => $contact['phone_number'],
                    'push_name' => $contact['push_name'],
                    'is_business' => $contact['is_business'],
                    'is_blocked' => $contact['is_blocked'],
                    'source' => 'waha',
                    'synced_at' => $now,
                    'raw_payload' => json_encode($contact['raw_payload']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            Contact::query()->upsert(
                $rows,
                ['session_id', 'chat_id'],
                [
                    'phone_number',
                    'push_name',
                    'is_business',
                    'is_blocked',
                    'source',
                    'synced_at',
                    'raw_payload',
                    'updated_at',
                ],
            );

            $summary['created'] = $selectedContacts
                ->reject(fn(array $contact) => $existingChatIds->has($contact['chat_id']))
                ->count();
            $summary['updated'] = $selectedContacts->count() - $summary['created'];
        }

        $summary['skipped'] = $summary['requested'] - $selectedContacts->count();

        Cache::forget($this->cacheKey($session));

        return $summary;
    }

    private function snapshot(WhatsappSession $session): array
    {
        return Cache::remember(
            $this->cacheKey($session),
            now()->addSeconds($this->cacheSeconds()),
            fn() => $this->buildSnapshot($session),
        );
    }

    private function buildSnapshot(WhatsappSession $session): array
    {
        $lidMap = $this->fetchLidMap($session);
        $normalized = collect($this->fetchContacts($session))
            ->map(fn(array $payload) => $this->normalize($payload, $lidMap))
            ->filter()
            ->sortBy(fn(array $contact) => $this->dedupeRank($contact))
            ->unique(fn(array $contact) => $this->dedupeKey($contact))
            ->sortBy(fn(array $contact) => $this->sortKey($contact), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $importable = $normalized->where('importable', true);
        $technical = $normalized->where('importable', false);

        return [
            'contacts' => $normalized->all(),
            'meta' => [
                'total_found' => $normalized->count(),
                'total_importable' => $importable->count(),
                'total_importable_with_name' => $importable->where('has_name', true)->count(),
                'total_importable_without_name' => $importable->where('has_name', false)->count(),
                'total_without_name' => $normalized->where('has_name', false)->count(),
                'total_skipped' => $technical->count(),
                'skipped_by_reason' => $technical
                    ->countBy('skip_reason')
                    ->all(),
                'cached_until' => now()->addSeconds($this->cacheSeconds())->toIso8601String(),
            ],
        ];
    }

    private function fetchContacts(WhatsappSession $session): array
    {
        $contacts = [];
        $sessionName = $this->remoteSessionName($session);

        for ($page = 0; $page < $this->maxPages(); $page++) {
            $offset = $page * $this->pageSize();
            $pageContacts = $this->waha->contacts($sessionName, $this->pageSize(), $offset);

            $contacts = array_merge($contacts, $pageContacts);

            if (count($pageContacts) < $this->pageSize()) {
                break;
            }
        }

        return $contacts;
    }

    private function fetchLidMap(WhatsappSession $session): array
    {
        $lids = [];
        $sessionName = $this->remoteSessionName($session);

        try {
            for ($page = 0; $page < $this->maxPages(); $page++) {
                $offset = $page * $this->pageSize();
                $pageLids = $this->waha->lids($sessionName, $this->pageSize(), $offset);

                foreach ($pageLids as $mapping) {
                    if (! isset($mapping['lid'])) {
                        continue;
                    }

                    $lids[$this->normalizeChatId((string) $mapping['lid'], 'lid')] = $mapping['pn'] ?? null;
                }

                if (count($pageLids) < $this->pageSize()) {
                    break;
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return $lids;
    }

    private function normalize(array $payload, array $lidMap = []): ?array
    {
        $chatId = $payload['id'] ?? $payload['chatId'] ?? null;

        if (! is_string($chatId) || $chatId === '') {
            return null;
        }

        if (($payload['isGroup'] ?? str_ends_with($chatId, '@g.us')) === true) {
            return null;
        }

        if (! $this->isUserContactId($chatId)) {
            return null;
        }

        $phoneNumber = $this->phoneNumber($payload, $chatId, $lidMap);
        $name = $this->humanName($chatId, $payload['name'] ?? null);
        $pushName = $this->firstFilled(
            $this->humanName($chatId, $payload['pushname'] ?? null),
            $name,
            $this->humanName($chatId, $payload['shortName'] ?? null),
        );
        $importable = $phoneNumber !== null || $pushName !== null;

        return [
            'chat_id' => $chatId,
            'phone_number' => $phoneNumber,
            'name' => $pushName,
            'push_name' => $pushName,
            'has_name' => $pushName !== null,
            'is_business' => (bool) ($payload['isBusiness'] ?? false),
            'is_blocked' => (bool) ($payload['isBlocked'] ?? false),
            'identifier_type' => str_ends_with($chatId, '@lid') ? 'lid' : 'phone',
            'importable' => $importable,
            'skip_reason' => $this->skipReason($chatId, $importable),
            'raw_payload' => $payload,
        ];
    }

    private function phoneNumber(array $payload, string $chatId, array $lidMap): ?string
    {
        if (str_ends_with($chatId, '@lid')) {
            return $this->phoneNumberFromChatId($lidMap[$this->normalizeChatId($chatId, 'lid')] ?? null);
        }

        if (isset($payload['number']) && $payload['number'] !== '') {
            return (string) $payload['number'];
        }

        return $this->phoneNumberFromChatId($chatId);
    }

    private function phoneNumberFromChatId(?string $chatId): ?string
    {
        if (! is_string($chatId) || $chatId === '') {
            return null;
        }

        $chatId = $this->normalizeChatId($chatId, 'c.us');

        return str_ends_with($chatId, '@c.us')
            ? str_replace('@c.us', '', $chatId)
            : null;
    }

    private function normalizeChatId(string $value, string $suffix): string
    {
        return str_contains($value, '@') ? $value : "{$value}@{$suffix}";
    }

    private function humanName(string $chatId, ?string $value): ?string
    {
        $value = $this->firstFilled($value);

        if (! $value) {
            return null;
        }

        if (preg_match('/^[\pP\pS\s]+$/u', $value) === 1) {
            return null;
        }

        $technicalValues = [
            $chatId,
            (string) preg_replace('/@(lid|c\.us)$/', '', $chatId),
        ];

        return in_array($value, $technicalValues, true) ? null : $value;
    }

    private function isUserContactId(string $chatId): bool
    {
        return str_ends_with($chatId, '@lid') || str_ends_with($chatId, '@c.us');
    }

    private function skipReason(string $chatId, bool $importable): ?string
    {
        if ($importable) {
            return null;
        }

        return str_ends_with($chatId, '@lid')
            ? 'lid_without_name_or_phone'
            : 'missing_name_or_phone';
    }

    private function sortKey(array $contact): string
    {
        $hasNameRank = $contact['has_name'] ? '0' : '1';
        $value = $this->firstFilled(
            $contact['name'] ?? null,
            $contact['push_name'] ?? null,
            $contact['phone_number'] ?? null,
            $contact['chat_id'] ?? null,
        ) ?? '';

        return $hasNameRank.'|'.$value;
    }

    private function dedupeKey(array $contact): string
    {
        return $contact['phone_number']
            ? 'phone:'.$contact['phone_number']
            : 'chat:'.$contact['chat_id'];
    }

    private function dedupeRank(array $contact): string
    {
        $identifierRank = $contact['identifier_type'] === 'phone' ? '0' : '1';
        $hasNameRank = $contact['has_name'] ? '0' : '1';

        return $this->dedupeKey($contact).'|'.$identifierRank.'|'.$hasNameRank;
    }

    private function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function remoteSessionName(WhatsappSession $session): string
    {
        return (string) ($session->metadata['waha_session'] ?? config('waha.session', 'default'));
    }

    private function cacheKey(WhatsappSession $session): string
    {
        return sprintf('contacts-preview:%s:%s', $session->id, $session->updated_at?->timestamp ?? 'new');
    }

    private function pageSize(): int
    {
        return max(100, (int) config('waha.contacts_page_size', 1000));
    }

    private function maxPages(): int
    {
        return max(1, (int) config('waha.contacts_max_pages', 20));
    }

    private function cacheSeconds(): int
    {
        return max(0, (int) config('waha.contacts_preview_cache_seconds', 300));
    }
}
