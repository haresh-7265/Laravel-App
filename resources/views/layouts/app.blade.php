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
</head>
<body>
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
    @stack('scripts')
</body>
</html>