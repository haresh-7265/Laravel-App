{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'App') — Laravel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100">

    {{-- Navbar --}}
    <nav class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <span class="font-semibold text-gray-800">MyApp</span>
        <div class="flex items-center gap-4 text-sm text-gray-600">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="text-red-500 hover:text-red-700 hover:underline">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    {{-- Flash messages --}}
    <div class="max-w-4xl mx-auto mt-4 px-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-300 text-green-800
                        text-sm px-4 py-3 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif
    </div>

    {{-- Page content --}}
    <main class="max-w-4xl mx-auto px-6 py-6">
        @yield('content')
    </main>

</body>
</html>