@extends('layouts.app')

@section('title', 'Onboarding - Waha Dash')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Configurar WhatsApp</h1>
                <p class="text-secondary mb-0">Conecte a sessao padrao para liberar o dashboard.</p>
            </div>

            @if ($step === 'create')
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Criar sessao</h2>
                        <p class="text-secondary">Escolha um nome amigavel para identificar esta sessao.</p>

                        <form method="POST" action="{{ route('onboarding.session.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="name">Nome da sessao</label>
                                <input
                                    class="form-control @error('name') is-invalid @enderror"
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    placeholder="Atendimento"
                                    required
                                >

                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="btn btn-success w-100" type="submit">Criar sessao</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($step === 'qr')
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <span class="badge text-bg-warning">Aguardando QR Code</span>
                        </div>

                        <h2 class="h5 mb-1">{{ $session->name }}</h2>
                        <p class="text-secondary mb-4">
                            Status WAHA:
                            <span data-onboarding-status>{{ $remote['status'] ?? 'nao sincronizada' }}</span>
                        </p>

                        <div
                            class="alert alert-info text-start"
                            role="status"
                            data-onboarding-polling
                        >
                            Aguardando leitura do QR Code.
                        </div>

                        @if ($remoteError)
                            <div class="alert alert-danger text-start" role="alert">
                                Nao foi possivel sincronizar com a WAHA.
                                <div class="small mt-1">{{ $remoteError }}</div>
                            </div>
                        @endif

                        <form class="mb-4" method="POST" action="{{ route('onboarding.qr.refresh') }}">
                            @csrf
                            <button class="btn btn-outline-secondary" type="submit">Gerar novo QR Code</button>
                        </form>

                        <div class="d-inline-block p-3 bg-white border rounded">
                            <img
                                class="img-fluid"
                                data-onboarding-qr
                                src="{{ route('onboarding.qr') }}?t={{ time() }}"
                                alt="QR Code do WhatsApp"
                                width="280"
                                height="280"
                            >
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@if ($step === 'qr')
    @push('scripts')
        <script
            data-qr-url="{{ route('onboarding.qr') }}"
            data-status-url="{{ route('onboarding.status') }}"
        >
            const onboardingScript = document.currentScript;
            const pollingAlert = document.querySelector('[data-onboarding-polling]');
            const statusText = document.querySelector('[data-onboarding-status]');
            const qrImage = document.querySelector('[data-onboarding-qr]');
            const qrUrl = onboardingScript.dataset.qrUrl;
            const statusUrl = onboardingScript.dataset.statusUrl;
            let lastQrRefresh = Date.now();

            async function pollSessionStatus() {
                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.error || 'Nao foi possivel consultar a sessao.');
                    }

                    statusText.textContent = payload.status || 'nao sincronizada';

                    if (payload.connected) {
                        pollingAlert.className = 'alert alert-success text-start';
                        pollingAlert.textContent = 'WhatsApp conectado. Abrindo dashboard...';
                        window.location.href = payload.redirect;
                        return;
                    }

                    if (['STARTING', 'SCAN_QR_CODE'].includes(payload.status) && shouldRefreshQr()) {
                        refreshQrImage();
                    }

                    pollingAlert.className = 'alert alert-info text-start';
                    pollingAlert.textContent = 'Aguardando leitura do QR Code.';
                } catch (error) {
                    pollingAlert.className = 'alert alert-warning text-start';
                    pollingAlert.textContent = error.message;
                }
            }

            function shouldRefreshQr() {
                return Date.now() - lastQrRefresh > 10000;
            }

            function refreshQrImage() {
                lastQrRefresh = Date.now();
                qrImage.src = `${qrUrl}?t=${lastQrRefresh}`;
            }

            setInterval(pollSessionStatus, 3000);
        </script>
    @endpush
@endif
