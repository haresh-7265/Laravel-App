<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {{-- Include --}}
    @include('partials.navbar') 

    <div class="container mt-4">

        {{-- section: header  --}}
        @hasSection('header')
            <div class="page-header mb-3">
                @yield('header')
            </div>
        @endif

        {{-- section: content --}}
        @yield('content')

    </div>

    {{-- section: scripts --}}
    @yield('scripts')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>