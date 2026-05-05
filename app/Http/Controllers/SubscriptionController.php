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
            ->with('status', 'Assinatura criada.');
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
            ->with('status', 'Assinatura atualizada.');
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
            ->with('status', 'Assinatura excluída.');
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
                'subscription' => $run->error_message ?: 'Não foi possível enviar a mensagem de teste.',
            ]);
    }

    public function clone(Subscription $subscription)
    {
        $session = WhatsappSession::query()->firstOrFail();
        abort_unless((int) $subscription->session_id === (int) $session->id, 404);

        // Obtém o contato de destino a partir da requisição.
        $contact_id = request()->post('contact_id');

        if (!$contact_id) {
            return redirect()
                ->route('subscriptions.index')
                ->withErrors([
                    'subscription' => 'Selecione um contato para clonar a assinatura.',
                ]);
        }

        // Verifica se o contato existe e pertence à sessão atual.
        $contact = Contact::where('session_id', $session->id)
            ->where('id', $contact_id)
            ->firstOrFail();

        // Cria uma cópia associada ao novo contato.
        $cloned = Subscription::create([
            'session_id' => $subscription->session_id,
            'contact_id' => $contact->id,
            'template_id' => $subscription->template_id,
            'title' => $subscription->title . ' (Cópia)',
            'description' => $subscription->description,
            'amount' => $subscription->amount,
            'frequency_unit' => $subscription->frequency_unit,
            'frequency_interval' => $subscription->frequency_interval,
            'start_date' => $subscription->start_date,
            'next_due_date' => $subscription->next_due_date,
            'send_time' => $subscription->send_time,
            'template_variables' => $subscription->template_variables,
            'status' => $subscription->status,
        ]);

        return redirect()
            ->route('subscriptions.index')
            ->with('status', 'Assinatura clonada com sucesso para ' . ($contact->display_name ?: $contact->phone_number) . '.');
    }
}
