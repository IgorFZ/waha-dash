<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <a class="navbar-brand fw-semibold" href="{{ route('dashboard') }}">Waha Dash</a>

            @yield('topbar')
        </div>
    </nav>

    <main class="container py-5">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
