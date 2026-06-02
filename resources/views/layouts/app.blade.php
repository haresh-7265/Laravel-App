@php
    $currentLocale = app()->getLocale();
    $isRtl = in_array($currentLocale, \App\Http\Middleware\SetLocale::RTL);
@endphp

<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @anyauth
        <meta name="user-id" content="{{ current_user()?->id }}">
        <meta name="user-guard" content="{{ current_guard() }}">
    @endanyauth
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{{ Str::headline($__env->yieldContent('title', config('admin.name'))) }}</title>
    <meta name="description" content="@yield('meta_description', config('app.name') . ' — Quality products at great prices.')">

    {{-- Bootstrap — load RTL stylesheet for RTL locales --}}
    @if($isRtl)
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    @yield('style')
    @stack('styles')
    @vite(['resources/js/app.js', "resources/js/helpers.js", "resources/css/app.css"])
    @admin
    @vite(['resources/js/admin/app.js'])
    @endadmin
</head>

<body>
    @if(session('impersonate.active'))
        <div class="bg-warning text-dark text-center py-2 fw-semibold shadow-sm">
            <i class="bi bi-person-fill-exclamation me-1"></i>
            You are impersonating: {{ current_user()?->name }} ({{ current_user()?->email }})
            <a href="{{ route('admin.stop-impersonate') }}" class="btn btn-sm btn-outline-dark ms-3 py-0.5 px-2 fw-bold">
                Stop Impersonation
            </a>
        </div>
    @endif

    {{-- ════ TOAST ════ --}}
    @include('partials.toast')

    @include('partials.navbar')
    @include('partials.sidebar')

    <div class="container mt-4">

        @yield('content')
    </div>

    @yield('footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @customer
        @vite('resources/js/customer/customer.js')
    @endcustomer
    @stack('scripts')
</body>

</html>