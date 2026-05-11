{{-- resources/views/partials/navbar.blade.php --}}
<nav class="bg-gray-900 text-white shadow sticky top-0 z-50">
    <div class="max-w-full mx-auto px-4 h-14 flex items-center gap-3">

        {{-- ── Sidebar toggle (inline in nav, not fixed) ─────────────── --}}
        <button id="sidebar-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" class="flex items-center justify-center w-8 h-8 rounded-lg
                   text-gray-300 hover:bg-gray-700 hover:text-white
                   transition flex-shrink-0" title="Menu">
            <i class="bi bi-list text-xl leading-none"></i>
        </button>

        {{-- ── Brand ──────────────────────────────────────────────────── --}}
        <a href="/" class="flex items-center justify-center w-8 h-8 flex-shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="logo" class="w-8 h-8 object-contain">
        </a>
        <a href="/" class="text-base font-semibold flex-shrink-0">
            {{ config('app.name') }}
        </a>

        {{-- ── Right side actions ──────────────────────────────────────── --}}
        <div class="flex items-center gap-4 ms-auto">

            {{-- Export (admin only) --}}
            @admin
            <a href="{{ route('products.export') }}" class="flex items-center gap-1.5 bg-gray-700 hover:bg-gray-600
                          text-sm px-3 py-1.5 rounded transition">
                <i class="bi bi-download text-lg"></i> Export
            </a>
            @endadmin

            {{-- Cart (guest + customer) --}}
            @if(!auth()->check() || auth()->user()->role === 'customer')
                <a href="{{ route('cart.index') }}" class="relative">
                    <i class="bi bi-cart text-xl"></i>
                    <span id="cart-badge" class="absolute -top-1.5 -end-1.5 bg-red-500 text-white text-[9px]
                                     min-w-[16px] h-4 flex items-center justify-center
                                     rounded-full px-0.5">
                        {{ $cart_count > 99 ? '99+' : ($cart_count ?: '0') }}
                    </span>
                </a>
            @endif

            {{-- Notification Bell --}}
            @include('partials.notification-bell')

            {{-- Profile avatar --}}
            @auth
                <a href="{{ route('profile.edit') }}" class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center
                              text-sm font-medium text-white hover:bg-blue-500 transition" title="Profile">
                    {{ Str::initials(auth()->user()?->name) }}
                </a>
            @endauth

            {{-- Guest login --}}
            @guest
                <a href="{{ route('login') }}" class="border border-gray-300 px-3 py-1 rounded text-sm
                              hover:bg-white hover:text-black transition">
                    Log in
                </a>
            @endguest

        </div>
    </div>
</nav>