<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Vite Assets --}}
    @vite(['resources/css/welcome.css', 'resources/js/welcome.js'])
</head>
<body>

    <div class="container text-center">
        <h1 class="title">Welcome to {{ config('app.name', 'Laravel App') }} {{ auth()->user() ? auth()->user()->name : 'Guest' }}</h1>
        <p class="subtitle">Build something amazing!</p>

        <button id="welcomeBtn" class="btn">Click Me</button>
    </div>

</body>
</html>