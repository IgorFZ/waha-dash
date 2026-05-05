@extends('layouts.app')

@section('title', 'Contatos - Waha Dash')

@section('topbar')
    <div class="d-inline-flex align-items-center gap-2 border border-success rounded px-3 py-2 bg-white">
        <span class="fw-semibold">{{ $session->name }}</span>
        <span class="badge text-bg-success">{{ $session->status->label() }}</span>
    </div>
@endsection

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Contatos</h1>
            <p class="text-secondary mb-0">Gerencie os contatos locais e importe contatos selecionados do WhatsApp.</p>
        </div>

        <button
            class="btn btn-success"
            type="button"
            data-contact-import-open
        >
            Buscar do WhatsApp
        </button>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form class="row g-2 mb-4" method="GET" action="{{ route('contacts.index') }}">
                <div class="col-md-9 col-lg-10">
                    <label class="visually-hidden" for="q">Buscar contatos</label>
                    <input
                        class="form-control"
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $search }}"
                        placeholder="Buscar por nome, telefone ou WhatsApp ID"
                    >
                </div>
                <div class="col-md-3 col-lg-2 d-grid">
                    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                </div>
            </form>

            @if ($contacts->isEmpty())
                <div class="text-center py-5">
                    <h2 class="h5 mb-2">Nenhum contato local ainda</h2>
                    <p class="text-secondary mb-4">Busque contatos no WhatsApp e escolha quais deseja importar.</p>
                    <button class="btn btn-success" type="button" data-contact-import-open>
                        Buscar do WhatsApp
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Nome</th>
                                <th scope="col">Telefone</th>
                                <th scope="col">WhatsApp ID</th>
                                <th scope="col">Origem</th>
                                <th scope="col">Sincronizado</th>
                                <th scope="col">Bloqueado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contacts as $contact)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $contact->display_name ?: ($contact->push_name ?: ($contact->phone_number ?: $contact->chat_id)) }}
                                        </div>
                                        @if ($contact->notes)
                                            <div class="small text-secondary">{{ $contact->notes }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $contact->phone_number ?: '-' }}</td>
                                    <td><code>{{ $contact->chat_id }}</code></td>
                                    <td>
                                        <span class="badge {{ $contact->source === 'waha' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $contact->source === 'waha' ? 'WAHA' : 'Manual' }}
                                        </span>
                                    </td>
                                    <td>{{ $contact->synced_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td>{{ $contact->is_blocked ? 'Sim' : 'Nao' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $contacts->links() }}
            @endif
        </div>
    </div>

    <div
        class="position-fixed top-0 start-0 end-0 bottom-0 bg-dark bg-opacity-50 d-none align-items-center justify-content-center p-3"
        style="z-index: 1050;"
        data-contact-import-modal
        data-preview-url="{{ route('contacts.import.preview') }}"
        data-import-url="{{ route('contacts.import') }}"
    >
        <div class="bg-white rounded shadow w-100" style="max-width: 960px; max-height: 90vh;">
            <div class="d-flex align-items-center justify-content-between gap-3 border-bottom p-4">
                <div>
                    <h2 class="h5 mb-1">Importar contatos do WhatsApp</h2>
                    <p class="text-secondary mb-0" data-contact-import-summary>Buscando contatos...</p>
                </div>
                <button class="btn-close" type="button" aria-label="Fechar" data-contact-import-close></button>
            </div>

            <div class="p-4">
                <div class="alert alert-danger d-none" role="alert" data-contact-import-error></div>
                <div class="alert alert-success d-none" role="status" data-contact-import-success></div>

                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between mb-3">
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <div class="form-check">
                            <input class="form-check-input" id="contact-import-select-all" type="checkbox" data-contact-import-select-all>
                            <label class="form-check-label" for="contact-import-select-all">
                                Selecionar todos os visiveis
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" id="contact-import-show-unnamed" type="checkbox" data-contact-import-show-unnamed>
                            <label class="form-check-label" for="contact-import-show-unnamed">
                                Mostrar sem nome
                            </label>
                        </div>
                    </div>

                    <div class="w-100" style="max-width: 360px;">
                        <label class="visually-hidden" for="contact-import-filter">Filtrar contatos</label>
                        <input
                            class="form-control"
                            id="contact-import-filter"
                            type="search"
                            placeholder="Filtrar nesta lista"
                            data-contact-import-filter
                        >
                    </div>
                </div>

                <div class="border rounded overflow-auto" style="max-height: 48vh;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light position-sticky top-0">
                            <tr>
                                <th scope="col" style="width: 48px;"></th>
                                <th scope="col">Nome</th>
                                <th scope="col">Telefone</th>
                                <th scope="col">WhatsApp ID</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody data-contact-import-list>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-5">Buscando contatos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-top p-4">
                <div class="text-secondary" data-contact-import-count>0 selecionados</div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" type="button" data-contact-import-close>Cancelar</button>
                    <button class="btn btn-success" type="button" data-contact-import-submit disabled>
                        Importar selecionados
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const importModal = document.querySelector('[data-contact-import-modal]');
        const openButtons = document.querySelectorAll('[data-contact-import-open]');
        const closeButtons = document.querySelectorAll('[data-contact-import-close]');
        const previewUrl = importModal.dataset.previewUrl;
        const importUrl = importModal.dataset.importUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const list = document.querySelector('[data-contact-import-list]');
        const summary = document.querySelector('[data-contact-import-summary]');
        const selectedCount = document.querySelector('[data-contact-import-count]');
        const errorAlert = document.querySelector('[data-contact-import-error]');
        const successAlert = document.querySelector('[data-contact-import-success]');
        const selectAll = document.querySelector('[data-contact-import-select-all]');
        const showUnnamed = document.querySelector('[data-contact-import-show-unnamed]');
        const filterInput = document.querySelector('[data-contact-import-filter]');
        const submitButton = document.querySelector('[data-contact-import-submit]');

        let previewContacts = [];
        let previewMeta = null;
        let unnamedLoaded = false;
        let selectedChatIds = new Set();

        openButtons.forEach((button) => button.addEventListener('click', openImportModal));
        closeButtons.forEach((button) => button.addEventListener('click', closeImportModal));
        selectAll.addEventListener('change', toggleVisibleContacts);
        showUnnamed.addEventListener('change', async () => {
            if (showUnnamed.checked && !unnamedLoaded) {
                await loadPreviewContacts(true);
                return;
            }

            renderContacts();
        });
        filterInput.addEventListener('input', renderContacts);
        submitButton.addEventListener('click', importSelectedContacts);
        list.addEventListener('change', (event) => {
            if (!event.target.matches('[data-contact-import-checkbox]')) {
                return;
            }

            if (event.target.checked) {
                selectedChatIds.add(event.target.value);
            } else {
                selectedChatIds.delete(event.target.value);
            }

            updateSelectionState();
        });

        async function openImportModal() {
            importModal.classList.remove('d-none');
            importModal.classList.add('d-flex');
            document.body.style.overflow = 'hidden';
            resetModal();

            await loadPreviewContacts(false);
        }

        async function loadPreviewContacts(includeUnnamed) {
            try {
                const url = includeUnnamed ? `${previewUrl}?include_unnamed=1` : previewUrl;
                const previousSelected = new Set(selectedChatIds);
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.error || payload.message || 'Nao foi possivel buscar contatos.');
                }

                previewContacts = payload.contacts || [];
                previewMeta = payload.meta || null;
                unnamedLoaded = includeUnnamed;
                selectedChatIds = new Set(
                    previewContacts
                        .filter((contact) => previousSelected.has(contact.chat_id)
                            || (contact.importable && contact.has_name && !contact.already_imported))
                        .map((contact) => contact.chat_id),
                );

                renderContacts();
            } catch (error) {
                showError(error.message);
                list.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-5">Nenhum contato carregado.</td></tr>';
                summary.textContent = 'Falha ao buscar contatos.';
                updateSelectionState();
            }
        }

        function closeImportModal() {
            importModal.classList.add('d-none');
            importModal.classList.remove('d-flex');
            document.body.style.overflow = '';
        }

        function resetModal() {
            previewContacts = [];
            previewMeta = null;
            unnamedLoaded = false;
            selectedChatIds = new Set();
            filterInput.value = '';
            showUnnamed.checked = false;
            hideAlert(errorAlert);
            hideAlert(successAlert);
            summary.textContent = 'Buscando contatos...';
            list.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-5">Buscando contatos...</td></tr>';
            updateSelectionState();
        }

        function renderContacts() {
            const contacts = visibleContacts();
            summary.textContent = previewSummaryText();

            if (previewContacts.length === 0) {
                list.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-5">Nenhum contato encontrado.</td></tr>';
                updateSelectionState();
                return;
            }

            if (contacts.length === 0) {
                list.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-5">Nenhum contato corresponde ao filtro.</td></tr>';
                updateSelectionState();
                return;
            }

            list.innerHTML = contacts.map((contact) => {
                const name = contact.name || contact.push_name || contact.phone_number || contact.chat_id;
                const checked = selectedChatIds.has(contact.chat_id) ? 'checked' : '';
                const disabled = contact.importable ? '' : 'disabled';
                const identifierBadge = contact.identifier_type === 'lid'
                    ? '<span class="badge text-bg-warning text-dark ms-2">LID</span>'
                    : '';
                const unnamedBadge = contact.has_name
                    ? ''
                    : '<span class="badge text-bg-light text-dark border ms-2">Sem nome</span>';
                const status = !contact.importable
                    ? `<span class="badge text-bg-light text-dark border">${skipReasonLabel(contact.skip_reason)}</span>`
                    : contact.already_imported
                    ? '<span class="badge text-bg-secondary">Ja importado</span>'
                    : '<span class="badge text-bg-success">Novo</span>';

                return `
                    <tr>
                        <td>
                            <input
                                class="form-check-input"
                                type="checkbox"
                                value="${escapeHtml(contact.chat_id)}"
                                aria-label="Selecionar contato"
                                data-contact-import-checkbox
                                ${checked}
                                ${disabled}
                            >
                        </td>
                        <td>${escapeHtml(name)}${identifierBadge}${unnamedBadge}</td>
                        <td>${escapeHtml(contact.phone_number || '-')}</td>
                        <td><code>${escapeHtml(contact.chat_id)}</code></td>
                        <td>${status}</td>
                    </tr>
                `;
            }).join('');

            updateSelectionState();
        }

        function visibleContacts() {
            const filter = filterInput.value.trim().toLowerCase();
            const includeUnnamed = showUnnamed.checked;

            return previewContacts.filter((contact) => {
                if (!includeUnnamed && !contact.has_name) {
                    return false;
                }

                if (!filter) {
                    return true;
                }

                const haystack = [
                    contact.name,
                    contact.push_name,
                    contact.phone_number,
                    contact.chat_id,
                ].filter(Boolean).join(' ').toLowerCase();

                return haystack.includes(filter);
            });
        }

        function skipReasonLabel(reason) {
            if (reason === 'lid_without_name_or_phone') {
                return 'LID sem nome';
            }

            return 'Sem nome';
        }

        function previewSummaryText() {
            if (!previewMeta) {
                return `${previewContacts.length} contatos encontrados no WhatsApp.`;
            }

            if (previewMeta.total_skipped > 0) {
                return `${previewMeta.total_importable} importaveis: ${previewMeta.total_importable_with_name} com nome, ${previewMeta.total_importable_without_name} sem nome. ${previewMeta.total_skipped} registros tecnicos.`;
            }

            return `${previewMeta.total_importable} importaveis: ${previewMeta.total_importable_with_name} com nome, ${previewMeta.total_importable_without_name} sem nome.`;
        }

        function toggleVisibleContacts() {
            visibleContacts().forEach((contact) => {
                if (!contact.importable) {
                    return;
                }

                if (selectAll.checked) {
                    selectedChatIds.add(contact.chat_id);
                } else {
                    selectedChatIds.delete(contact.chat_id);
                }
            });

            renderContacts();
        }

        async function importSelectedContacts() {
            const chatIds = Array.from(selectedChatIds);

            if (chatIds.length === 0) {
                return;
            }

            hideAlert(errorAlert);
            hideAlert(successAlert);
            submitButton.disabled = true;
            submitButton.textContent = 'Importando...';

            try {
                const response = await fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        chat_ids: chatIds,
                    }),
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Nao foi possivel importar os contatos.');
                }

                const summary = payload.summary;
                showSuccess(
                    `Importacao concluida: ${summary.created} criados, ${summary.updated} atualizados, ${summary.skipped} ignorados.`,
                );

                window.setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                showError(error.message);
                submitButton.disabled = false;
            } finally {
                submitButton.textContent = 'Importar selecionados';
                updateSelectionState();
            }
        }

        function updateSelectionState() {
            const visible = visibleContacts();
            const importableVisible = visible.filter((contact) => contact.importable);
            const visibleSelected = importableVisible.filter((contact) => selectedChatIds.has(contact.chat_id)).length;

            selectedCount.textContent = `${selectedChatIds.size} selecionados`;
            submitButton.disabled = selectedChatIds.size === 0;
            selectAll.checked = importableVisible.length > 0 && visibleSelected === importableVisible.length;
            selectAll.indeterminate = visibleSelected > 0 && visibleSelected < importableVisible.length;
        }

        function showError(message) {
            errorAlert.textContent = message;
            errorAlert.classList.remove('d-none');
        }

        function showSuccess(message) {
            successAlert.textContent = message;
            successAlert.classList.remove('d-none');
        }

        function hideAlert(element) {
            element.textContent = '';
            element.classList.add('d-none');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
@endpush
