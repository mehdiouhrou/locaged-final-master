<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/L LOGO.svg') }}" type="image/x-icon">
    <!-- Webfont: Inter for consistent Helvetica-like rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=block" rel="stylesheet">
    <title>{{ config('app.name', 'Locaged') }}</title>
</head>
<body class="locaged-v2 locaged-v2-guest">
@php($loginCoverUrl = \App\Support\Branding::loginImageUrl())
<div class="container-fluid ps-0">
    <div class="row flex-column flex-md-row" style="min-height: 100vh; overflow: hidden;">
        {{-- Bannière visible sur mobile (avant la zone login) --}}
        <div class="col-12 d-md-none p-0 order-first">
            <div
                class="w-100 guest-login-cover-bg guest-login-cover-bg--mobile"
                style="background-image: url('{{ e($loginCoverUrl) }}');"
                role="img"
                aria-hidden="true"
            ></div>
        </div>
        <div class="col-lg-8 col-md-7 d-none d-md-block p-0">
            <div
                class="w-100 h-100 guest-login-cover-bg guest-login-cover-bg--desktop"
                style="background-image: url('{{ e($loginCoverUrl) }}');"
                role="img"
                aria-hidden="true"
            ></div>
        </div>
        <div class="col-lg-4 col-md-5 col-12 mx-auto px-5 px-md-0 d-flex align-items-center justify-content-center order-last order-md-last py-4 py-md-0">
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
