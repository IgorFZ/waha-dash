@extends('layouts.app')

@section('title', 'Templates - Waha Dash')

@section('content')
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Templates</h1>
            <p class="text-secondary mb-0">Crie mensagens reutilizaveis para envio automatico nas subscriptions.</p>
        </div>

        <button class="btn btn-success" type="button" data-template-open>
            Novo template
        </button>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Nao foi possivel concluir a acao.</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($templates->isEmpty())
                <div class="text-center py-5">
                    <h2 class="h5 mb-2">Nenhum template cadastrado</h2>
                    <p class="text-secondary mb-4">Crie o primeiro template para usar nas subscriptions.</p>
                    <button class="btn btn-success" type="button" data-template-open>
                        Novo template
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Titulo</th>
                                <th scope="col">Body</th>
                                <th scope="col">Midia</th>
                                <th scope="col">Uso</th>
                                <th scope="col" class="text-end">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($templates as $template)
                                <tr>
                                    <td class="fw-semibold">{{ $template->title }}</td>
                                    <td class="text-secondary" style="max-width: 520px;">
                                        {{ str($template->body)->limit(140) }}
                                    </td>
                                    <td>
                                        @if ($template->media_url)
                                            <span class="badge text-bg-secondary">{{ $template->media_type?->value ?? 'midia' }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $template->subscriptions_count }} subscriptions</td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button
                                                class="btn btn-sm btn-outline-secondary"
                                                type="button"
                                                data-template-edit
                                                data-action="{{ route('message-templates.update', $template) }}"
                                                data-title="{{ $template->title }}"
                                                data-body="{{ $template->body }}"
                                                data-media-url="{{ $template->media_url }}"
                                                data-media-type="{{ $template->media_type?->value }}"
                                            >
                                                Editar
                                            </button>

                                            <form
                                                method="POST"
                                                action="{{ route('message-templates.destroy', $template) }}"
                                                data-template-delete-form
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="btn btn-sm btn-outline-danger"
                                                    type="submit"
                                                    @disabled($template->subscriptions_count > 0)
                                                    title="{{ $template->subscriptions_count > 0 ? 'Template usado em subscription nao pode ser excluido.' : 'Excluir template' }}"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $templates->links() }}
            @endif
        </div>
    </div>

    <div
        class="position-fixed top-0 start-0 end-0 bottom-0 bg-dark bg-opacity-50 d-none align-items-center justify-content-center p-3"
        style="z-index: 1050;"
        data-template-modal
    >
        <div class="bg-white rounded shadow w-100 overflow-auto" style="max-width: 980px; max-height: 92vh;">
            <div class="d-flex align-items-center justify-content-between gap-3 border-bottom p-4">
                <div>
                    <h2 class="h5 mb-1" data-template-title>Novo template</h2>
                    <p class="text-secondary mb-0">Use placeholders com chaves duplas, como <code>@{{ contact.name }}</code>.</p>
                </div>
                <button class="btn-close" type="button" aria-label="Fechar" data-template-close></button>
            </div>

            <form method="POST" action="{{ route('message-templates.store') }}" data-template-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-template-method disabled>

                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="mb-3">
                                <label class="form-label" for="template-title">Título</label>
                                <input
                                    class="form-control"
                                    id="template-title"
                                    name="title"
                                    type="text"
                                    maxlength="255"
                                    required
                                    data-template-field-title
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="template-body">Corpo da Mensagem</label>
                                <textarea
                                    class="form-control"
                                    id="template-body"
                                    name="body"
                                    rows="10"
                                    maxlength="5000"
                                    required
                                    data-template-body
                                ></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label" for="template-media-type">Tipo de mídia</label>
                                    <select class="form-select" id="template-media-type" name="media_type" data-template-media-type>
                                        <option value="">Sem mídia</option>
                                        @foreach ($mediaTypes as $mediaType)
                                            <option value="{{ $mediaType->value }}">{{ ucfirst($mediaType->value) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label" for="template-media-url">URL da mídia</label>
                                    <input
                                        class="form-control"
                                        id="template-media-url"
                                        name="media_url"
                                        type="url"
                                        maxlength="2048"
                                        placeholder="https://..."
                                        data-template-media-url
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="mb-3">
                                <div class="form-label">Placeholders</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($availablePlaceholders as $placeholder)
                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            type="button"
                                            data-placeholder="{{ $placeholder }}"
                                        >
                                            &#123;&#123; {{ $placeholder }} &#125;&#125;
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <div class="form-label">Prévia</div>
                                <div class="border rounded p-3 bg-body-tertiary" style="min-height: 220px; white-space: pre-wrap;" data-template-preview></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top p-4">
                    <button class="btn btn-outline-secondary" type="button" data-template-close>Cancelar</button>
                    <button class="btn btn-success" type="submit" data-template-submit>Salvar template</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const templateModal = document.querySelector('[data-template-modal]');
        const templateOpenButtons = document.querySelectorAll('[data-template-open]');
        const templateCloseButtons = document.querySelectorAll('[data-template-close]');
        const templateEditButtons = document.querySelectorAll('[data-template-edit]');
        const templateDeleteForms = document.querySelectorAll('[data-template-delete-form]');
        const templateForm = document.querySelector('[data-template-form]');
        const templateMethod = document.querySelector('[data-template-method]');
        const templateTitle = document.querySelector('[data-template-title]');
        const titleField = document.querySelector('[data-template-field-title]');
        const bodyField = document.querySelector('[data-template-body]');
        const mediaUrlField = document.querySelector('[data-template-media-url]');
        const mediaTypeField = document.querySelector('[data-template-media-type]');
        const preview = document.querySelector('[data-template-preview]');
        const submitButton = document.querySelector('[data-template-submit]');
        const exampleContext = @json($exampleContext);

        templateOpenButtons.forEach((button) => button.addEventListener('click', openCreateTemplateModal));
        templateCloseButtons.forEach((button) => button.addEventListener('click', closeTemplateModal));
        templateEditButtons.forEach((button) => button.addEventListener('click', () => openEditTemplateModal(button)));
        templateDeleteForms.forEach((form) => form.addEventListener('submit', (event) => {
            if (!confirm('Excluir este template?')) {
                event.preventDefault();
            }
        }));
        bodyField.addEventListener('input', renderPreview);
        document.querySelectorAll('[data-placeholder]').forEach((button) => {
            button.addEventListener('click', () => insertPlaceholder(button.dataset.placeholder));
        });
        templateForm.addEventListener('submit', () => {
            submitButton.disabled = true;
            submitButton.textContent = 'Salvando...';
        });

        function openCreateTemplateModal() {
            templateTitle.textContent = 'Novo template';
            templateForm.action = '{{ route('message-templates.store') }}';
            templateMethod.disabled = true;
            titleField.value = '';
            bodyField.value = 'Ola @{{ contact.name }}, sua assinatura @{{ subscription.title }} vence em @{{ subscription.next_due_date }} no valor de R$ @{{ subscription.amount }}.';
            mediaUrlField.value = '';
            mediaTypeField.value = '';
            submitButton.disabled = false;
            submitButton.textContent = 'Salvar template';
            renderPreview();
            openTemplateModal();
        }

        function openEditTemplateModal(button) {
            templateTitle.textContent = 'Editar template';
            templateForm.action = button.dataset.action;
            templateMethod.disabled = false;
            titleField.value = button.dataset.title || '';
            bodyField.value = button.dataset.body || '';
            mediaUrlField.value = button.dataset.mediaUrl || '';
            mediaTypeField.value = button.dataset.mediaType || '';
            submitButton.disabled = false;
            submitButton.textContent = 'Salvar template';
            renderPreview();
            openTemplateModal();
        }

        function openTemplateModal() {
            templateModal.classList.remove('d-none');
            templateModal.classList.add('d-flex');
            document.body.style.overflow = 'hidden';
            titleField.focus();
        }

        function closeTemplateModal() {
            templateModal.classList.add('d-none');
            templateModal.classList.remove('d-flex');
            document.body.style.overflow = '';
        }

        function insertPlaceholder(placeholder) {
            const token = `@{{ ${placeholder} }}`;
            const start = bodyField.selectionStart;
            const end = bodyField.selectionEnd;

            bodyField.value = `${bodyField.value.slice(0, start)}${token}${bodyField.value.slice(end)}`;
            bodyField.focus();
            bodyField.selectionStart = bodyField.selectionEnd = start + token.length;
            renderPreview();
        }

        function renderPreview() {
            const placeholderPattern = new RegExp('\\x7B\\x7B\\s*([a-zA-Z0-9_.-]+)\\s*\\x7D\\x7D', 'g');

            preview.textContent = bodyField.value.replace(placeholderPattern, (match, key) => {
                const value = key.split('.').reduce((carry, segment) => carry?.[segment], exampleContext);

                return value ?? '';
            });
        }
    </script>
@endpush
