<?php

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionSender;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:send-due {--limit=100}', function (SubscriptionSender $sender) {
    $limit = max(1, (int) $this->option('limit'));

    $subscriptions = Subscription::query()
        ->with(['contact', 'template', 'session'])
        ->where('status', SubscriptionStatus::Active->value)
        ->whereDate('next_due_date', '<=', now()->toDateString())
        ->orderBy('next_due_date')
        ->limit($limit)
        ->get();

    if ($subscriptions->isEmpty()) {
        $this->info('Nenhuma subscription vencida para envio.');

        return 0;
    }

    $sent = 0;
    $failed = 0;

    foreach ($subscriptions as $subscription) {
        $run = $sender->send($subscription, advanceNextDueDate: true);

        if ($run->wasSuccessful()) {
            $sent++;
            $this->line("Enviado: #{$subscription->id} {$subscription->title}");
        } else {
            $failed++;
            $this->warn("Falhou: #{$subscription->id} {$subscription->title} - {$run->error_message}");
        }
    }

    $this->info("Concluido. Enviados: {$sent}. Falhas: {$failed}.");

    return $failed > 0 ? 1 : 0;
})->purpose('Send due subscription messages through WhatsApp');
