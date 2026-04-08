<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('admin.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    @yield('style')
    @stack('styles')
    @vite(['resources/js/app.js', "resources/js/helpers.js"])
    @admin
    @vite(['resources/js/admin/app.js'])
    @endadmin
</head>
<body>

    {{-- ════ TOAST ════ --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">

        <div id="orderToast" class="toast align-items-center text-bg-dark border-0" role="alert">

            <div class="d-flex">
                <div class="toast-body" id="toastBody" style="cursor:pointer;">
                    <!-- dynamic content -->
                </div>

                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast" aria-label="Close"></button>
            </div>

        </div>

    </div>
 
    @include('partials.navbar')

    <div class="container mt-4">

        {{-- Success / Error Alerts --}}
        @foreach (['success', 'warning', 'info', 'danger'] as $type)
            @if (session($type))
                <x-alert type="{{ $type }}">
                    {{ session($type) }}
                </x-alert>
            @endif
        @endforeach

        @yield('content')
    </div>

    @yield('footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @auth
        @if (auth()->user()->isCustomer())
            @vite('resources/js/customer/customer.js')
        @endif
    @endauth
    @stack('scripts')
</body>
</html>