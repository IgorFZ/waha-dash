@extends('layouts.app')

@section('title', 'Painel - Waha Dash')

@section('content')
    <div class="py-4 mb-4">
        <h1 class="h3 mb-1">Painel</h1>
        <p class="text-secondary mb-0">Visão geral do seu sistema de gerenciamento de assinaturas.</p>
    </div>

    <!-- Metrics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1">Total de Contatos</p>
                            <h2 class="h3 mb-0">{{ $totalContacts }}</h2>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem; opacity: 0.2;">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1">Total de Assinaturas</p>
                            <h2 class="h3 mb-0">{{ $totalSubscriptions }}</h2>
                        </div>
                        <div class="text-success" style="font-size: 2.5rem; opacity: 0.2;">
                            <i class="bi bi-bell"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1">Mensagens Enviadas</p>
                            <h2 class="h3 mb-0">{{ $totalMessagesSent }}</h2>
                        </div>
                        <div class="text-info" style="font-size: 2.5rem; opacity: 0.2;">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Assinaturas nos últimos 6 meses</h5>
            <canvas id="subscriptionsChart" style="max-height: 400px;"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        const ctx = document.getElementById('subscriptionsChart').getContext('2d');
        const data = @json($subscriptionsLast6Months);

        const months = data.map(item => item.month);
        const counts = data.map(item => item.count);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Assinaturas criadas',
                    data: counts,
                    borderColor: 'rgb(75, 192, 75)',
                    backgroundColor: 'rgba(75, 192, 75, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgb(75, 192, 75)',
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
@endpush
