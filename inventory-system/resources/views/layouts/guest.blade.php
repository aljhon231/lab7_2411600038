<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') | {{ config('app.name', 'Inventory System') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container-fluid min-vh-100">
        <div class="row min-vh-100">
            <section class="col-lg-6 d-none d-lg-flex login-brand">
                <div class="brand-content">
                    <div class="brand-icon"><i class="bi bi-box-seam"></i></div>
                    <h1>Inventory System</h1>
                    <p>Keep construction materials organized, monitor stock, and record every movement in one place.</p>
                    <div class="brand-features">
                        <div><i class="bi bi-check-circle-fill"></i> Product and supplier records</div>
                        <div><i class="bi bi-check-circle-fill"></i> Low-stock alerts</div>
                        <div><i class="bi bi-check-circle-fill"></i> Stock movement history</div>
                    </div>
                </div>
            </section>
            <main class="col-lg-6 d-flex align-items-center justify-content-center">
                <div class="login-container">
                    <div class="mobile-brand d-lg-none text-center">
                        <div class="brand-icon"><i class="bi bi-box-seam"></i></div>
                        <h2>Inventory System</h2>
                    </div>
                    <div class="card login-card">
                        <div class="card-body p-4 p-md-5">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
