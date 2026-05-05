<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Waha Dash')</title>

    @stack('head')

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <a class="navbar-brand fw-semibold me-0" href="{{ route('dashboard') }}">Waha Dash</a>
                <div class="navbar-nav flex-row gap-2">
                    <a
                        class="nav-link {{ request()->routeIs('contacts.*') ? 'active fw-semibold' : '' }}"
                        href="{{ route('contacts.index') }}"
                    >
                        Contatos
                    </a>
                </div>
            </div>

            @yield('topbar')
        </div>
    </nav>

    <main class="container py-5">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
