@extends('layouts.app')

@section('title', 'Assinaturas - Waha Dash')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Assinaturas</h1>
            <p class="text-secondary mb-0">Configure cobranças recorrentes e variáveis dinâmicas para os modelos.</p>
        </div>

        <button class="btn btn-success" type="button" data-subscription-open @disabled($contacts->isEmpty() || $templates->isEmpty())>
            Nova assinatura
        </button>
    </div>

    @if ($contacts->isEmpty() || $templates->isEmpty())
        <div class="alert alert-warning" role="alert">
            Cadastre ao menos um contato e um modelo antes de criar assinaturas.
        </div>
    @endif

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Não foi possível concluir a ação.</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($subscriptions->isEmpty())
                <div class="text-center py-5">
                    <h2 class="h5 mb-2">Nenhuma assinatura cadastrada</h2>
                    <p class="text-secondary mb-4">Crie uma assinatura para automatizar mensagens recorrentes.</p>
                    <button class="btn btn-success" type="button" data-subscription-open @disabled($contacts->isEmpty() || $templates->isEmpty())>
                        Nova assinatura
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Título</th>
                                <th scope="col">Contato</th>
                                <th scope="col">Modelo</th>
                                <th scope="col">Valor</th>
                                <th scope="col">Frequência</th>
                                <th scope="col">Próximo envio</th>
                                <th scope="col">Status</th>
                                <th scope="col">Execuções</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subscriptions as $subscription)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $subscription->title }}</div>
                                        @if ($subscription->description)
                                            <div class="small text-secondary">{{ $subscription->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $subscription->contact?->display_name ?: ($subscription->contact?->push_name ?: $subscription->contact?->phone_number) }}</td>
                                    <td>{{ $subscription->template?->title }}</td>
                                    <td>R$ {{ number_format((float) $subscription->amount, 2, ',', '.') }}</td>
                                    <td>{{ $subscription->frequencyDescription() }}</td>
                                    <td>{{ $subscription->next_due_date?->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge {{ $subscription->isActive() ? 'text-bg-success' : ($subscription->isCancelled() ? 'text-bg-secondary' : 'text-bg-warning') }}">
                                            {{ $subscription->status->label() }}
                                        </span>
                                    </td>
                                    <td>{{ $subscription->runs_count }}</td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST" action="{{ route('subscriptions.test-send', $subscription) }}" data-subscription-test-form>
                                                @csrf
                                                <button
                                                    class="btn btn-sm btn-outline-success"
                                                    type="submit"
                                                    @disabled(! $subscription->isActive())
                                                    title="{{ $subscription->isActive() ? 'Enviar mensagem de teste agora' : 'Apenas assinaturas ativas podem ser testadas' }}"
                                                >
                                                    Testar envio
                                                </button>
                                            </form>

                                            <button
                                                class="btn btn-sm btn-outline-secondary"
                                                type="button"
                                                data-subscription-edit
                                                data-action="{{ route('subscriptions.update', $subscription) }}"
                                                data-contact-id="{{ $subscription->contact_id }}"
                                                data-template-id="{{ $subscription->template_id }}"
                                                data-title="{{ $subscription->title }}"
                                                data-description="{{ $subscription->description }}"
                                                data-amount="{{ $subscription->amount }}"
                                                data-frequency-unit="{{ $subscription->frequency_unit->value }}"
                                                data-frequency-interval="{{ $subscription->frequency_interval }}"
                                                data-start-date="{{ $subscription->start_date?->format('Y-m-d') }}"
                                                data-next-due-date="{{ $subscription->next_due_date?->format('Y-m-d') }}"
                                                data-send-time="{{ $subscription->send_time?->format('H:i') }}"
                                                data-status="{{ $subscription->status->value }}"
                                                data-variables='@json($subscription->template_variables ?? [])'
                                            >
                                                Editar
                                            </button>

                                            <button
                                                class="btn btn-sm btn-outline-info"
                                                type="button"
                                                data-subscription-clone
                                                data-subscription-id="{{ $subscription->id }}"
                                                title="Clonar esta assinatura para outro contato"
                                            >
                                                Clonar
                                            </button>

                                            <form method="POST" action="{{ route('subscriptions.destroy', $subscription) }}" data-subscription-delete-form>
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $subscriptions->links() }}
            @endif
        </div>
    </div>

    <div
        class="position-fixed top-0 start-0 end-0 bottom-0 bg-dark bg-opacity-50 d-none align-items-center justify-content-center p-3"
        style="z-index: 1050;"
        data-subscription-modal
    >
        <div class="bg-white rounded shadow w-100 overflow-auto" style="max-width: 1120px; max-height: 92vh;">
            <div class="d-flex align-items-center justify-content-between gap-3 border-bottom p-4">
                <div>
                    <h2 class="h5 mb-1" data-subscription-title>Nova assinatura</h2>
                    <p class="text-secondary mb-0">As variáveis ficam disponíveis no modelo como <code>@{{ variables.nome }}</code>.</p>
                </div>
                <button class="btn-close" type="button" aria-label="Fechar" data-subscription-close></button>
            </div>

            <form method="POST" action="{{ route('subscriptions.store') }}" data-subscription-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-subscription-method disabled>

                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="subscription-contact">Contato</label>
                                    <select class="form-select" id="subscription-contact" name="contact_id" required data-subscription-contact>
                                        <option value="">Selecione</option>
                                        @foreach ($contacts as $contact)
                                            <option
                                                value="{{ $contact->id }}"
                                                data-name="{{ $contact->display_name ?: ($contact->push_name ?: $contact->phone_number) }}"
                                                data-phone="{{ $contact->phone_number }}"
                                            >
                                                {{ $contact->display_name ?: ($contact->push_name ?: $contact->phone_number) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="subscription-template">Modelo</label>
                                    <select class="form-select" id="subscription-template" name="template_id" required data-subscription-template>
                                        <option value="">Selecione</option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}" data-body="{{ $template->body }}">
                                                {{ $template->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label" for="subscription-title">Título</label>
                                    <input class="form-control" id="subscription-title" name="title" type="text" maxlength="255" required data-subscription-field="title">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="subscription-amount">Valor</label>
                                    <input class="form-control" id="subscription-amount" name="amount" type="number" min="0" max="99999999.99" step="0.01" required data-subscription-field="amount">
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="subscription-description">Descrição</label>
                                    <textarea class="form-control" id="subscription-description" name="description" rows="2" maxlength="2000" data-subscription-field="description"></textarea>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="subscription-frequency-unit">Frequência</label>
                                    <select class="form-select" id="subscription-frequency-unit" name="frequency_unit" required data-subscription-field="frequency_unit">
                                        @foreach ($frequencyUnits as $frequencyUnit)
                                            <option value="{{ $frequencyUnit->value }}">{{ $frequencyUnit->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="subscription-frequency-interval">Intervalo</label>
                                    <input class="form-control" id="subscription-frequency-interval" name="frequency_interval" type="number" min="1" max="120" value="1" required data-subscription-field="frequency_interval">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="subscription-status">Status</label>
                                    <select class="form-select" id="subscription-status" name="status" required data-subscription-field="status">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="subscription-start-date">Início</label>
                                    <input class="form-control" id="subscription-start-date" name="start_date" type="date" required data-subscription-field="start_date">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="subscription-next-due-date">Próximo envio</label>
                                    <input class="form-control" id="subscription-next-due-date" name="next_due_date" type="date" data-subscription-field="next_due_date">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="subscription-send-time">Horário do envio</label>
                                    <input class="form-control" id="subscription-send-time" name="send_time" type="time" data-subscription-field="send_time">
                                </div>
                            </div>

                            <div class="border-top mt-4 pt-4">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <div class="form-label mb-0">Variáveis dinâmicas</div>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-variable-add>Adicionar variável</button>
                                </div>

                                <div class="d-flex flex-column gap-2" data-variables-list></div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="form-label">Prévia da mensagem</div>
                            <div class="border rounded p-3 bg-body-tertiary" style="min-height: 420px; white-space: pre-wrap;" data-subscription-preview></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top p-4">
                    <button class="btn btn-outline-secondary" type="button" data-subscription-close>Cancelar</button>
                    <button class="btn btn-success" type="submit" data-subscription-submit>Salvar assinatura</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Clone Modal -->
    <div
        class="position-fixed top-0 start-0 end-0 bottom-0 bg-dark bg-opacity-50 d-none align-items-center justify-content-center p-3"
        style="z-index: 1050;"
        data-clone-modal
    >
        <div class="bg-white rounded shadow" style="max-width: 500px; width: 100%;">
            <div class="d-flex align-items-center justify-content-between gap-3 border-bottom p-4">
                <h2 class="h5 mb-0">Clonar assinatura</h2>
                <button class="btn-close" type="button" aria-label="Fechar" data-clone-close></button>
            </div>

            <form method="POST" data-clone-form>
                @csrf
                <div class="p-4">
                    <label class="form-label" for="clone-contact">Selecione o contato de destino:</label>
                    <select class="form-select" id="clone-contact" name="contact_id" required data-clone-contact>
                        <option value="">Selecione um contato</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}">
                                {{ $contact->display_name ?: ($contact->push_name ?: $contact->phone_number) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top p-4">
                    <button class="btn btn-outline-secondary" type="button" data-clone-close>Cancelar</button>
                    <button class="btn btn-info" type="submit">Clonar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const modal = document.querySelector('[data-subscription-modal]');
        const openButtons = document.querySelectorAll('[data-subscription-open]');
        const closeButtons = document.querySelectorAll('[data-subscription-close]');
        const editButtons = document.querySelectorAll('[data-subscription-edit]');
        const deleteForms = document.querySelectorAll('[data-subscription-delete-form]');
        const testForms = document.querySelectorAll('[data-subscription-test-form]');
        const form = document.querySelector('[data-subscription-form]');
        const method = document.querySelector('[data-subscription-method]');
        const modalTitle = document.querySelector('[data-subscription-title]');
        const submitButton = document.querySelector('[data-subscription-submit]');
        const contactSelect = document.querySelector('[data-subscription-contact]');
        const templateSelect = document.querySelector('[data-subscription-template]');
        const preview = document.querySelector('[data-subscription-preview]');
        const variablesList = document.querySelector('[data-variables-list]');
        const addVariableButton = document.querySelector('[data-variable-add]');
        const exampleContext = @json($exampleContext);

        openButtons.forEach((button) => button.addEventListener('click', openCreateModal));
        closeButtons.forEach((button) => button.addEventListener('click', closeModal));
        editButtons.forEach((button) => button.addEventListener('click', () => openEditModal(button)));
        deleteForms.forEach((form) => form.addEventListener('submit', (event) => {
            if (!confirm('Excluir esta assinatura?')) {
                event.preventDefault();
            }
        }));
        testForms.forEach((form) => form.addEventListener('submit', (event) => {
            if (!confirm('Enviar uma mensagem de teste agora?')) {
                event.preventDefault();
            }
        }));
        addVariableButton.addEventListener('click', () => addVariableRow('', ''));
        form.addEventListener('input', renderPreview);
        form.addEventListener('change', renderPreview);
        form.addEventListener('submit', () => {
            submitButton.disabled = true;
            submitButton.textContent = 'Salvando...';
        });
        variablesList.addEventListener('click', (event) => {
            if (event.target.matches('[data-variable-remove]')) {
                event.target.closest('[data-variable-row]').remove();
                reindexVariables();
                renderPreview();
            }
        });

        function openCreateModal() {
            modalTitle.textContent = 'Nova assinatura';
            form.action = '{{ route('subscriptions.store') }}';
            method.disabled = true;
            form.reset();
            document.querySelector('[data-subscription-field="frequency_interval"]').value = 1;
            document.querySelector('[data-subscription-field="status"]').value = 'active';
            document.querySelector('[data-subscription-field="start_date"]').value = new Date().toISOString().slice(0, 10);
            resetVariables({
                pix_key: '',
                payment_link: '',
            });
            submitButton.disabled = false;
            submitButton.textContent = 'Salvar assinatura';
            renderPreview();
            openModal();
        }

        function openEditModal(button) {
            modalTitle.textContent = 'Editar assinatura';
            form.action = button.dataset.action;
            method.disabled = false;
            setField('contact_id', button.dataset.contactId);
            setField('template_id', button.dataset.templateId);
            setField('title', button.dataset.title);
            setField('description', button.dataset.description);
            setField('amount', button.dataset.amount);
            setField('frequency_unit', button.dataset.frequencyUnit);
            setField('frequency_interval', button.dataset.frequencyInterval);
            setField('start_date', button.dataset.startDate);
            setField('next_due_date', button.dataset.nextDueDate);
            setField('send_time', button.dataset.sendTime);
            setField('status', button.dataset.status);
            resetVariables(JSON.parse(button.dataset.variables || '{}'));
            submitButton.disabled = false;
            submitButton.textContent = 'Salvar assinatura';
            renderPreview();
            openModal();
        }

        function openModal() {
            modal.classList.remove('d-none');
            modal.classList.add('d-flex');
            document.body.style.overflow = 'hidden';
            contactSelect.focus();
        }

        function closeModal() {
            modal.classList.add('d-none');
            modal.classList.remove('d-flex');
            document.body.style.overflow = '';
        }

        function setField(name, value) {
            const field = form.querySelector(`[name="${name}"]`);

            if (field) {
                field.value = value || '';
            }
        }

        function resetVariables(variables) {
            variablesList.innerHTML = '';

            const entries = Object.entries(variables);
            if (entries.length === 0) {
                addVariableRow('pix_key', '');
                addVariableRow('payment_link', '');
                return;
            }

            entries.forEach(([key, value]) => addVariableRow(key, value));
            reindexVariables();
        }

        function addVariableRow(key, value) {
            const index = variablesList.querySelectorAll('[data-variable-row]').length;
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center';
            row.dataset.variableRow = '';
            row.innerHTML = `
                <div class="col-md-4">
                    <input class="form-control" name="variables[${index}][key]" type="text" value="${escapeHtml(key)}" placeholder="pix_key" data-variable-key>
                </div>
                <div class="col-md-7">
                    <input class="form-control" name="variables[${index}][value]" type="text" value="${escapeHtml(value)}" placeholder="Valor" data-variable-value>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-outline-danger" type="button" data-variable-remove aria-label="Remover variável">x</button>
                </div>
            `;
            variablesList.appendChild(row);
        }

        function reindexVariables() {
            variablesList.querySelectorAll('[data-variable-row]').forEach((row, index) => {
                row.querySelector('[data-variable-key]').name = `variables[${index}][key]`;
                row.querySelector('[data-variable-value]').name = `variables[${index}][value]`;
            });
        }

        function renderPreview() {
            const templateBody = templateSelect.selectedOptions[0]?.dataset.body || '';
            const context = currentContext();
            const placeholderPattern = new RegExp('\\x7B\\x7B\\s*([a-zA-Z0-9_.-]+)\\s*\\x7D\\x7D', 'g');

            preview.textContent = templateBody.replace(placeholderPattern, (match, key) => {
                const value = key.split('.').reduce((carry, segment) => carry?.[segment], context);

                return value ?? '';
            }) || 'Selecione um template para visualizar a mensagem.';
        }

        function currentContext() {
            const contactOption = contactSelect.selectedOptions[0];
            const amount = Number(form.querySelector('[name="amount"]').value || 0);

            return {
                contact: {
                    name: contactOption?.dataset.name || exampleContext.contact.name,
                    phone_number: contactOption?.dataset.phone || exampleContext.contact.phone_number,
                },
                subscription: {
                    title: form.querySelector('[name="title"]').value || exampleContext.subscription.title,
                    description: form.querySelector('[name="description"]').value || exampleContext.subscription.description,
                    amount: amount.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                    frequency: frequencyLabel(),
                    start_date: formatDate(form.querySelector('[name="start_date"]').value) || exampleContext.subscription.start_date,
                    next_due_date: formatDate(form.querySelector('[name="next_due_date"]').value) || exampleContext.subscription.next_due_date,
                },
                session: exampleContext.session,
                variables: currentVariables(),
            };
        }

        function currentVariables() {
            const variables = {};

            variablesList.querySelectorAll('[data-variable-row]').forEach((row) => {
                const key = row.querySelector('[data-variable-key]').value.trim();
                const value = row.querySelector('[data-variable-value]').value.trim();

                if (key) {
                    variables[key] = value;
                }
            });

            return variables;
        }

        function frequencyLabel() {
            const unit = form.querySelector('[name="frequency_unit"]').value;
            const interval = Number(form.querySelector('[name="frequency_interval"]').value || 1);
            const labels = {
                weekly: interval === 1 ? 'Semanal' : `a cada ${interval} semanas`,
                monthly: interval === 1 ? 'Mensal' : `a cada ${interval} meses`,
                yearly: interval === 1 ? 'Anual' : `a cada ${interval} anos`,
            };

            return labels[unit] || '';
        }

        function formatDate(value) {
            if (!value) {
                return '';
            }

            const [year, month, day] = value.split('-');
            return `${day}/${month}/${year}`;
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        // Clone functionality
        const cloneModal = document.querySelector('[data-clone-modal]');
        const cloneForm = document.querySelector('[data-clone-form]');
        const cloneCloseButtons = document.querySelectorAll('[data-clone-close]');
        const cloneButtons = document.querySelectorAll('[data-subscription-clone]');

        cloneButtons.forEach((button) => button.addEventListener('click', () => openCloneModal(button)));
        cloneCloseButtons.forEach((button) => button.addEventListener('click', closeCloneModal));

        function openCloneModal(button) {
            const subscriptionId = button.dataset.subscriptionId;
            cloneForm.action = `/subscriptions/${subscriptionId}/clone`;
            cloneForm.reset();
            cloneModal.classList.remove('d-none');
            cloneModal.classList.add('d-flex');
            document.body.style.overflow = 'hidden';
            document.querySelector('[data-clone-contact]').focus();
        }

        function closeCloneModal() {
            cloneModal.classList.add('d-none');
            cloneModal.classList.remove('d-flex');
            document.body.style.overflow = '';
        }
    </script>
@endpush
