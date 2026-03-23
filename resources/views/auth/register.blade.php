@extends('layouts.auth')
@section('title', 'Register')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Create account</h1>
        <p class="text-gray-500 text-sm mt-1">Fill in your details to get started</p>
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

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        {{-- Name --}}
        <div class="mb-4">
            <label for="name"
                   class="block text-sm font-medium text-gray-700 mb-1">
                Full name
            </label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                class="w-full border rounded-lg px-3 py-2 text-sm outline-none
                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                       @error('name') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                placeholder="John Doe"
            >
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

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
                <span class="text-gray-400 font-normal">(min. 8 characters)</span>
            </label>
            <input
                type="password"
                id="password"
                name="password"
                required
                class="w-full border rounded-lg px-3 py-2 text-sm outline-none
                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                       @error('password') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                placeholder="••••••••"
            >
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm password --}}
        <div class="mb-6">
            <label for="password_confirmation"
                   class="block text-sm font-medium text-gray-700 mb-1">
                Confirm password
            </label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                       outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="••••••••"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium
                   py-2.5 px-4 rounded-lg text-sm transition-colors duration-150">
            Create account
        </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
        Already have an account?
        <a href="{{ route('login') }}"
           class="text-blue-600 hover:underline font-medium">
            Sign in
        </a>
    </p>
</div>
@endsection