<?php

namespace App\Http\Controllers;

use App\Enums\FrequencyUnit;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\Subscriptions\StoreSubscriptionRequest;
use App\Http\Requests\Subscriptions\UpdateSubscriptionRequest;
use App\Models\Contact;
use App\Models\MessageTemplate;
use App\Models\Subscription;
use App\Models\WhatsappSession;
use App\Services\Templates\MessageTemplateRenderer;
use App\Services\Subscriptions\SubscriptionSender;

class SubscriptionController extends Controller
{
    public function index(MessageTemplateRenderer $renderer)
    {
        $session = WhatsappSession::query()->first();

        if (! $session) {
            return redirect()->route('onboarding.show');
        }

        $subscriptions = Subscription::query()
            ->with(['contact', 'template'])
            ->withCount('runs')
            ->where('session_id', $session->id)
            ->orderByRaw("case status when 'active' then 0 when 'paused' then 1 else 2 end")
            ->orderBy('next_due_date')
            ->paginate(15);

        return view('subscriptions.index', [
            'subscriptions' => $subscriptions,
            'contacts' => Contact::query()
                ->where('session_id', $session->id)
                ->orderByRaw("lower(coalesce(nullif(display_name, ''), nullif(push_name, ''), nullif(phone_number, ''), chat_id)) asc")
                ->get(),
            'templates' => MessageTemplate::query()->orderBy('title')->get(),
            'frequencyUnits' => FrequencyUnit::cases(),
            'statuses' => SubscriptionStatus::cases(),
            'exampleContext' => $renderer->exampleContext(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $session = WhatsappSession::query()->firstOrFail();

        Subscription::query()->create([
            'session_id' => $session->id,
            ...$request->subscriptionData(),
        ]);

        return redirect()
            ->route('subscriptions.index')
            ->with('status', 'Subscription criada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $session = WhatsappSession::query()->firstOrFail();
        abort_unless((int) $subscription->session_id === (int) $session->id, 404);

        $subscription->update($request->subscriptionData());

        return redirect()
            ->route('subscriptions.index')
            ->with('status', 'Subscription atualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $session = WhatsappSession::query()->firstOrFail();
        abort_unless((int) $subscription->session_id === (int) $session->id, 404);

        $subscription->delete();

        return redirect()
            ->route('subscriptions.index')
            ->with('status', 'Subscription excluida.');
    }

    public function testSend(Subscription $subscription, SubscriptionSender $sender)
    {
        $session = WhatsappSession::query()->firstOrFail();
        abort_unless((int) $subscription->session_id === (int) $session->id, 404);

        $run = $sender->send($subscription, advanceNextDueDate: false);

        if ($run->wasSuccessful()) {
            return redirect()
                ->route('subscriptions.index')
                ->with('status', 'Mensagem de teste enviada.');
        }

        return redirect()
            ->route('subscriptions.index')
            ->withErrors([
                'subscription' => $run->error_message ?: 'Nao foi possivel enviar a mensagem de teste.',
            ]);
    }
}
