<?php

namespace App\Services\Templates;

use App\Models\Contact;
use App\Models\MessageTemplate;
use App\Models\Subscription;
use App\Models\WhatsappSession;

class MessageTemplateRenderer
{
    public function render(MessageTemplate|string $template, array $context): string
    {
        $body = $template instanceof MessageTemplate ? $template->body : $template;

        return preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_.-]+)\s*}}/',
            fn(array $matches) => (string) data_get($context, $matches[1], ''),
            $body,
        ) ?? $body;
    }

    public function placeholders(MessageTemplate|string $template): array
    {
        $body = $template instanceof MessageTemplate ? $template->body : $template;

        preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $body, $matches);

        return collect($matches[1] ?? [])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function contextForSubscription(Subscription $subscription): array
    {
        $subscription->loadMissing(['contact', 'session', 'template']);

        return [
            'contact' => $this->contactContext($subscription->contact),
            'subscription' => [
                'title' => $subscription->title,
                'description' => $subscription->description,
                'amount' => number_format((float) $subscription->amount, 2, ',', '.'),
                'frequency' => $subscription->frequencyDescription(),
                'start_date' => $subscription->start_date?->format('d/m/Y'),
                'next_due_date' => $subscription->next_due_date?->format('d/m/Y'),
            ],
            'session' => $this->sessionContext($subscription->session),
            'variables' => $subscription->template_variables ?? [],
        ];
    }

    public function exampleContext(): array
    {
        return [
            'contact' => [
                'name' => 'Maria Silva',
                'phone_number' => '554999999999',
            ],
            'subscription' => [
                'title' => 'Mensalidade',
                'description' => 'Plano mensal',
                'amount' => '99,90',
                'frequency' => 'a cada 1 mês',
                'start_date' => now()->format('d/m/Y'),
                'next_due_date' => now()->addMonth()->format('d/m/Y'),
            ],
            'session' => [
                'name' => 'Atendimento',
                'phone_number' => '554988888888',
            ],
            'variables' => [
                'pix_key' => 'financeiro@example.com',
                'payment_link' => 'https://example.com/pagar',
            ],
        ];
    }

    public function availablePlaceholders(): array
    {
        return [
            'contact.name',
            'contact.phone_number',
            'subscription.title',
            'subscription.description',
            'subscription.amount',
            'subscription.frequency',
            'subscription.start_date',
            'subscription.next_due_date',
            'session.name',
            'session.phone_number',
            'variables.pix_key',
            'variables.payment_link',
        ];
    }

    private function contactContext(?Contact $contact): array
    {
        return [
            'name' => $contact?->display_name ?: ($contact?->push_name ?: $contact?->phone_number),
            'phone_number' => $contact?->phone_number,
        ];
    }

    private function sessionContext(?WhatsappSession $session): array
    {
        return [
            'name' => $session?->name,
            'phone_number' => $session?->phone_number,
        ];
    }
}
