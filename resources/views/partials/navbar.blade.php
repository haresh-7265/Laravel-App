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
            @can('manage-products')
            <a href="{{ route('products.export') }}" class="flex items-center gap-1.5 bg-gray-700 hover:bg-gray-600
                          text-sm px-3 py-1.5 rounded transition">
                <i class="bi bi-download text-lg"></i> Export
            </a>
            @endcan

            {{-- Cart (guest + customer) --}}
            @can('view', \App\Models\Cart::class)
                <a href="{{ route('cart.index') }}" class="relative">
                    <i class="bi bi-cart text-xl"></i>
                    <span id="cart-badge" class="absolute -top-1.5 -end-1.5 bg-red-500 text-white text-[9px]
                                     min-w-[16px] h-4 flex items-center justify-center
                                     rounded-full px-0.5">
                        {{ $cart_count > 99 ? '99+' : ($cart_count ?: '0') }}
                    </span>
            </a>
            @endcan

            {{-- Locale Switcher --}}
            <div class="relative inline-block text-left" id="locale-dropdown-wrapper">
                <button id="locale-dropdown-button" type="button" class="flex items-center gap-1 bg-transparent hover:bg-gray-800 text-gray-300 hover:text-white text-xs px-2.5 py-1.5 rounded transition focus:outline-none" aria-haspopup="true" aria-expanded="false">
                    <span>{{ strtoupper(app()->getLocale()) }}</span>
                    <i id="locale-dropdown-arrow" class="bi bi-chevron-down text-[10px] leading-none transition-transform duration-200"></i>
                </button>
                <div id="locale-dropdown-menu" class="hidden absolute end-0 mt-2 w-20 rounded-md shadow-lg bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="locale-dropdown-button">
                        @foreach(\App\Http\Middleware\SetLocale::SUPPORTED as $supportedLocale)
                            <form action="{{ route('locale.switch') }}" method="POST" class="block w-full">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="locale" value="{{ $supportedLocale }}">
                                <button type="submit" class="block w-full text-start px-4 py-2 text-xs text-gray-300 hover:bg-gray-700 hover:text-white transition {{ app()->getLocale() === $supportedLocale ? 'bg-gray-700/50 text-white font-semibold' : '' }}" role="menuitem">
                                    {{ strtoupper($supportedLocale) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Notification Bell --}}
            @include('partials.notification-bell')

            {{-- Profile avatar (Initials) --}}
            @anyauth
                <a href="{{ route('profile.edit') }}" 
                   class="w-8 h-8 rounded-full bg-blue-600 hover:bg-blue-700 flex items-center justify-center text-xs font-semibold text-white transition-colors" 
                   title="Profile">
                    {{ Str::initials(current_user()?->name) }}
                </a>
            @endanyauth

            {{-- Guest login --}}
            @if(is_guest())
                <a href="{{ route('login') }}" class="border border-gray-200 text-gray-700 px-3.5 py-1.5 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-colors">
                    Log in
                </a>
            @endif

        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('locale-dropdown-wrapper');
    if (!wrapper) return;
    
    const button = document.getElementById('locale-dropdown-button');
    const menu = document.getElementById('locale-dropdown-menu');
    const arrow = document.getElementById('locale-dropdown-arrow');
    
    button.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = !menu.classList.contains('hidden');
        if (isOpen) {
            menu.classList.add('hidden');
            arrow.classList.remove('bi-chevron-up');
            arrow.classList.add('bi-chevron-down');
            button.setAttribute('aria-expanded', 'false');
        } else {
            menu.classList.remove('hidden');
            arrow.classList.remove('bi-chevron-down');
            arrow.classList.add('bi-chevron-up');
            button.setAttribute('aria-expanded', 'true');
        }
    });
    
    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            menu.classList.add('hidden');
            arrow.classList.remove('bi-chevron-up');
            arrow.classList.add('bi-chevron-down');
            button.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>
