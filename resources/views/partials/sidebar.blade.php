{{-- resources/views/partials/sidebar.blade.php --}}
{{--
    Toggle button lives in navbar — NOT here.
    This file = overlay + panel + JS only.
--}}

@php
    $adminLinks = [
    ['route' => 'admin.dashboard',          'icon' => 'bi-speedometer2',    'label' => 'Dashboard',        'permission' => 'view-admin-dashboard'],
    ['route' => 'admin.orders.index',       'icon' => 'bi-receipt',         'label' => 'Orders',             'permission' => 'manage_orders'],
    ['route' => 'admin.online-customers',   'icon' => 'bi-people',          'label' => 'Online',             'permission' => 'manage_users'],
    ['route' => 'admin.cache.index',        'icon' => 'bi-speedometer',     'label' => 'Cache',              'permission' => 'manage_cache'],
    ['route' => 'admin.analytics.index',    'icon' => 'bi-graph-up-arrow',  'label' => 'Sales',              'permission' => 'view_reports'],
    ['route' => 'admin.invoices.index',     'icon' => 'bi-receipt-cutoff',  'label' => 'Invoices',           'permission' => 'manage_orders'],
    ['route' => 'admin.files.index',        'icon' => 'bi-bar-chart-line',  'label' => 'Reports',            'permission' => 'view_reports'],
    ['route' => 'admin.slow-queries.index', 'icon' => 'bi-hourglass-split', 'label' => 'Slow Queries',       'permission' => 'manage_cache'],
    ['route' => 'admin.roles.index',        'icon' => 'bi-people-fill',     'label' => 'Manage Users',       'permission' => 'manage_users'],
];

$menuLinks = [
    ['route' => 'products.create',    'icon' => 'bi-plus-circle',           'label' => 'Create Product',     'permission' => 'manage_products'],
    ['route' => 'products.trashed',   'icon' => 'bi-archive',               'label' => 'Trashed Products',   'permission' => 'manage_products'],
    ['route' => 'products.import.index', 'icon' => 'bi-file-earmark-arrow-up', 'label' => 'Import Products',    'permission' => 'manage_products'],
];
@endphp

{{-- Overlay --}}
<div id="sidebar-overlay"
     class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm
            opacity-0 pointer-events-none transition-opacity duration-300"
     aria-hidden="true">
</div>

{{-- Panel --}}
<aside id="sidebar"
       role="navigation"
       aria-label="Main navigation"
       class="fixed top-0 start-0 z-50 h-full w-52
              bg-gray-800 text-gray-300 flex flex-col
              transition-transform duration-300 ease-in-out shadow-2xl">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 h-14 border-b border-gray-700 flex-shrink-0">
        <span class="text-sm font-semibold text-white tracking-wide">
            {{ config('app.name') }}
        </span>
        <button id="sidebar-close"
                aria-label="Close navigation"
                class="flex items-center justify-center w-7 h-7 rounded
                       text-gray-400 hover:text-white hover:bg-gray-700 transition">
            <i class="bi bi-x-lg text-sm leading-none"></i>
        </button>
    </div>

    {{-- Scrollable links --}}
    <div class="flex-1 overflow-y-auto py-2">

        <div class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-widest text-gray-500">Menu</div>

        <a href="{{ route('products.index') }}" @class([
            'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
            'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs('products.index'),
        ])>
            <i class="bi bi-grid w-4 text-center"></i> Products
        </a>

        {{-- @can('manage-products') --}}
            @foreach($menuLinks as $link)
                @if(is_null($link['permission']) || Gate::check($link['permission']))
                <a href="{{ route($link['route']) }}" @class([
                    'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
                    'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs($link['route']),
                ])>
                    <i class="{{ $link['icon'] }} w-4 text-center"></i> {{ $link['label'] }}
                </a>
                @endif
            @endforeach
        {{-- @endcan --}}

        @anyauth
            @customer
                @role('customer')
                <a href="{{ route('orders.index') }}" @class([
                    'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
                    'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs('orders.index'),
                ])>
                    <i class="bi bi-bag w-4 text-center"></i> My Orders
                </a>
                @endrole
                <a href="{{ route('user.devices') }}" @class([
                    'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
                    'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs('user.devices'),
                ])>
                    <i class="bi bi-phone w-4 text-center"></i> My Devices
                </a>
                <a href="{{ route('user.api-keys') }}" @class([
                    'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
                    'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs('user.api-keys'),
                ])>
                    <i class="bi bi-key w-4 text-center"></i> API Keys
                </a>
            @endcustomer

            <a href="{{ route('notifications.index') }}" @class([
                'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
                'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs('notifications.index'),
            ])>
                <i class="bi bi-bell w-4 text-center"></i> Notifications
                @php
                    $sidebarUnread = current_user()->unreadNotifications()->count();
                @endphp
                @if($sidebarUnread > 0)
                    <span class="ms-auto bg-indigo-500 text-white text-[10px] font-semibold min-w-[20px] h-5 flex items-center justify-center rounded-full px-1" id="sidebar-unread">
                        {{ $sidebarUnread > 99 ? '99+' : $sidebarUnread }}
                    </span>
                @endif
            </a>
        @endanyauth

        {{-- @can('view-admin-dashboard') --}}
            @foreach($adminLinks as $link)
                @if(is_null($link['permission']) || Gate::check($link['permission']))
                <div class="px-3 pt-5 pb-1 text-[10px] uppercase tracking-widest text-gray-500">Admin</div>
                @break
                @endif
            @endforeach

            @foreach($adminLinks as $link)
            @if(is_null($link['permission']) || Gate::check($link['permission']))
                <a href="{{ route($link['route']) }}" @class([
                    'flex items-center gap-2.5 px-4 py-2.5 text-sm transition hover:bg-gray-700',
                    'bg-gray-700 border-s-2 border-blue-500 text-white' => request()->routeIs($link['route']),
                ])>
                    <i class="{{ $link['icon'] }} w-4 text-center"></i> {{ $link['label'] }}
                </a>
            @endif
            @endforeach
        {{-- @endcan --}}

    </div>

    {{-- Footer --}}
    <div class="px-3 py-4 border-t border-gray-700 flex-shrink-0">
        @admin
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button class="w-full border border-red-500 text-red-400 py-1.5 rounded
                               text-sm hover:bg-red-500 hover:text-white transition">
                    Admin Logout
                </button>
            </form>
        @endadmin
        @customer
            <form action="{{ route('logout') }}" method="POST" class="mt-2">
                @csrf
                <button class="w-full border border-red-500 text-red-400 py-1.5 rounded
                               text-sm hover:bg-red-500 hover:text-white transition">
                    Log out
                </button>
            </form>
        @endcustomer
        @if(is_guest())
            <a href="{{ route('login') }}"
               class="block text-center text-sm text-gray-400 hover:text-white transition">
                Log in
            </a>
        @endif
    </div>
</aside>

{{-- JS --}}
<script>
(function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle  = document.getElementById('sidebar-toggle'); // lives in navbar
    const close   = document.getElementById('sidebar-close');
    const isRtl   = document.documentElement.dir === 'rtl';
    const hiddenClass = isRtl ? 'translate-x-full' : '-translate-x-full';

    // Set initial hidden state via JS (avoids rtl: Tailwind variant mismatch)
    sidebar.classList.add(hiddenClass);

    function openSidebar() {
        sidebar.classList.remove(hiddenClass);
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100');
        toggle?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add(hiddenClass);
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.classList.remove('opacity-100');
        toggle?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    toggle?.addEventListener('click', openSidebar);
    close.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', e => e.key === 'Escape' && closeSidebar());
})();
</script>