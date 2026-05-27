@extends('layouts.auth')
@section('title', 'Login')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
        <p class="text-gray-500 text-sm mt-1">Sign in to your account</p>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm
                    px-4 py-3 rounded-lg mb-6">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form — CSRF token required --}}
    <form method="POST" action="{{ route('login.store') }}" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="email"
                   class="block text-sm font-medium text-gray-700 mb-1">
                Email address
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full border rounded-lg px-3 py-2 text-sm outline-none
                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                       @error('email') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                placeholder="you@example.com"
            >
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <label for="password"
                   class="block text-sm font-medium text-gray-700 mb-1">
                Password
            </label>
            <input
                type="password"
                id="password"
                name="password"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                       outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="••••••••"
            >
        </div>

        {{-- CAPTCHA --}}
        @if(session('captcha_question'))
            <div class="mb-4 bg-gray-50 border border-gray-200 rounded-lg p-3">
                <label for="captcha_answer" class="block text-sm font-medium text-gray-700 mb-1">
                    Security Verification: <strong>{{ session('captcha_question') }}</strong>
                </label>
                <input
                    type="text"
                    id="captcha_answer"
                    name="captcha_answer"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('captcha_answer') border-red-400 bg-red-50 @enderror"
                    placeholder="Enter the answer"
                    required
                >
                @error('captcha_answer')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Remember me --}}
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-gray-300">
                Remember me
            </label>
        </div>

        {{-- Submit --}}
        <div class="flex-col items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mb-2" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium
                   py-2.5 px-4 rounded-lg text-sm transition-colors duration-150">
            Sign in
        </button>
        </div>
    </form>

    {{-- Register link --}}
    <p class="text-center text-sm text-gray-500 mt-6">
        Don't have an account?
        <a href="{{ route('register') }}"
           class="text-blue-600 hover:underline font-medium">
            Create one
        </a>
    </p>
</div>
@endsection