<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('admin.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    @yield('style')
    @vite(['resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">{{ config('admin.name') }}</a>
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('products.index') }}">Products</a>
                @if(auth()->user()->role == 'customer')
                    <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                        <i class="bi bi-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ array_sum(array_column(session('cart', []), 'qty')) }}
                        </span>
                    </a>
                @endif
                @admin
                <a class="nav-link" href="{{ route('products.create') }}">Create</a>
                @endadmin
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="nav-link btn btn-link">
                        LogOut
                    </button>
                </form>
            </div>
        </div>
    </nav>

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