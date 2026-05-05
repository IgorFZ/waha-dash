<?php

namespace App\Services\Subscriptions;

use App\Enums\DeliveryStatus;
use App\Enums\RunStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionRun;
use App\Services\Templates\MessageTemplateRenderer;
use App\Services\Waha\WahaClient;

class SubscriptionSender
{
    public function __construct(
        private readonly MessageTemplateRenderer $renderer,
        private readonly WahaClient $waha,
    ) {
    }

    public function send(Subscription $subscription, bool $advanceNextDueDate = false): SubscriptionRun
    {
        $subscription->loadMissing(['contact', 'template', 'session']);

        $run = SubscriptionRun::query()->create([
            'subscription_id' => $subscription->id,
            'status' => RunStatus::Pending,
            'cycle_due_date' => $subscription->next_due_date ?? now()->toDateString(),
            'started_at' => now(),
        ]);

        if ($subscription->status !== SubscriptionStatus::Active) {
            $run->update([
                'status' => RunStatus::Skipped,
                'error_message' => 'Subscription nao esta ativa.',
                'finished_at' => now(),
            ]);

            return $run;
        }

        if (! $subscription->contact?->chat_id) {
            $run->update([
                'status' => RunStatus::Failed,
                'delivery_status' => DeliveryStatus::Failed,
                'error_message' => 'Contato sem WhatsApp ID.',
                'finished_at' => now(),
            ]);

            return $run;
        }

        try {
            $message = $this->renderer->render(
                $subscription->template,
                $this->renderer->contextForSubscription($subscription),
            );

            $response = $this->waha->sendText(
                $subscription->contact->chat_id,
                $message,
                $this->remoteSessionName($subscription),
            );

            $run->update([
                'status' => RunStatus::Success,
                'message_sent' => $message,
                'external_message_id' => $this->externalMessageId($response),
                'delivery_status' => DeliveryStatus::Sent,
                'finished_at' => now(),
            ]);

            if ($advanceNextDueDate) {
                $subscription->update([
                    'last_sent_at' => now(),
                    'next_due_date' => $subscription->frequency_unit->advance(
                        $subscription->next_due_date ?? now(),
                        $subscription->frequency_interval,
                    )->toDateString(),
                ]);
            }
        } catch (\Throwable $exception) {
            $run->update([
                'status' => RunStatus::Failed,
                'delivery_status' => DeliveryStatus::Failed,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        }

        return $run->fresh();
    }

    private function remoteSessionName(Subscription $subscription): string
    {
        return (string) ($subscription->session?->metadata['waha_session'] ?? config('waha.session', 'default'));
    }

    private function externalMessageId(array $response): ?string
    {
        $id = $response['id']
            ?? $response['_data']['id']['id']
            ?? $response['key']['id']
            ?? null;

        if (is_array($id)) {
            return json_encode($id);
        }

        return is_scalar($id) ? (string) $id : null;
    }
}
