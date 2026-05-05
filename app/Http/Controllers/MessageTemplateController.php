<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Http\Requests\MessageTemplates\StoreMessageTemplateRequest;
use App\Http\Requests\MessageTemplates\UpdateMessageTemplateRequest;
use App\Models\MessageTemplate;
use App\Services\Templates\MessageTemplateRenderer;

class MessageTemplateController extends Controller
{
    public function index(MessageTemplateRenderer $renderer)
    {
        $templates = MessageTemplate::query()
            ->withCount('subscriptions')
            ->orderBy('title')
            ->paginate(15);

        return view('message_templates.index', [
            'templates' => $templates,
            'mediaTypes' => MediaType::cases(),
            'availablePlaceholders' => $renderer->availablePlaceholders(),
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
    public function store(StoreMessageTemplateRequest $request)
    {
        MessageTemplate::query()->create($request->validated());

        return redirect()
            ->route('message-templates.index')
            ->with('status', 'Template criado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MessageTemplate $messageTemplate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MessageTemplate $messageTemplate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $messageTemplate)
    {
        $messageTemplate->update($request->validated());

        return redirect()
            ->route('message-templates.index')
            ->with('status', 'Template atualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MessageTemplate $messageTemplate)
    {
        if ($messageTemplate->subscriptions()->exists()) {
            return redirect()
                ->route('message-templates.index')
                ->withErrors([
                    'template' => 'Template usado em assinaturas não pode ser excluído.',
                ]);
        }

        $messageTemplate->delete();

        return redirect()
            ->route('message-templates.index')
            ->with('status', 'Template excluído.');
    }
}
