<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contacts\ImportContactsRequest;
use App\Http\Requests\Contacts\IndexContactsRequest;
use App\Models\Contact;
use App\Models\WhatsappSession;
use App\Services\Contacts\ContactImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(IndexContactsRequest $request)
    {
        $session = WhatsappSession::query()->first();

        if (! $session) {
            return redirect()->route('onboarding.show');
        }

        $search = $request->search();

        $contacts = Contact::query()
            ->where('session_id', $session->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('display_name', 'like', "%{$search}%")
                        ->orWhere('push_name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('chat_id', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("(coalesce(nullif(display_name, ''), nullif(push_name, '')) is null) asc")
            ->orderByRaw("lower(coalesce(nullif(display_name, ''), nullif(push_name, ''), nullif(phone_number, ''), chat_id)) asc")
            ->paginate(15)
            ->withQueryString();

        return view('contacts.index', [
            'contacts' => $contacts,
            'session' => $session,
            'search' => $search,
        ]);
    }

    public function importPreview(Request $request, ContactImportService $contacts): JsonResponse
    {
        $session = WhatsappSession::query()->firstOrFail();

        try {
            return response()->json($contacts->preview(
                $session,
                $request->boolean('include_unnamed'),
            ));
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Nao foi possivel buscar os contatos na WAHA.',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function import(ImportContactsRequest $request, ContactImportService $contacts): JsonResponse
    {
        $session = WhatsappSession::query()->firstOrFail();
        $summary = $contacts->importSelected($session, $request->chatIds());

        return response()->json([
            'message' => 'Importacao concluida.',
            'summary' => $summary,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        //
    }
}
