<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('admin.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">{{ config('admin.name') }}</a>
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('products.index') }}">Products</a>
                @if (auth()->user()->isAdmin())
                <a class="nav-link" href="{{ route('products.create') }}">Create</a>
                @endif
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        {{-- Success / Error Alerts --}}
        @if(session('success'))
            <x-alert type="success">
                {{ session('success') }}
            </x-alert>
        @endif

        @if(session('error'))
            <x-alert type="danger">
                {{ session('error') }}
            </x-alert>
        @endif

        @yield('content')
    </div>

    @yield('footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>