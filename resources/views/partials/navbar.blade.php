<nav class="bg-gray-900 text-white shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">

        <div class="flex justify-between items-center h-16">

            <!-- Logo -->
            <a href="/" class="text-lg font-semibold">
                {{ config('admin.name') }}
            </a>

            <!-- Toggle -->
            <button id="menu-btn" class="lg:hidden">
                <i class="bi bi-list text-2xl"></i>
            </button>

            <!-- Menu -->
            <div id="menu"
                class="hidden lg:flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-5 absolute lg:static top-16 left-0 w-full lg:w-auto bg-gray-900 lg:bg-transparent px-4 lg:px-0 py-4 lg:py-0">

                <!-- Products -->
                <a href="{{ route('products.index') }}"
                   class=" {{ request()->routeIs('products.index') ? 'text-blue-400' : '' }}">
                    Products
                </a>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('orders.index') }}"
                           class=" {{ request()->routeIs('orders.index') ? 'text-blue-400' : '' }}">
                            My Orders
                        </a>
                    @endif
                @endauth

                @if(!auth()->check() || auth()->user()->role === 'customer')
                    <!-- Cart -->
                    <a href="{{ route('cart.index') }}"
                       class="relative  {{ request()->routeIs('cart.index') ? 'text-blue-400' : '' }}">
                        <i class="bi bi-cart text-xl"></i>

                        <span id="cart-badge"
                              class="absolute top-0 right-0 translate-x-1/2 -translate-y-1/2 
                                     bg-red-500 text-white text-[10px] min-w-[18px] h-[18px] 
                                     flex items-center justify-center rounded-full">
                            {{ $cart_count > 0 ? ($cart_count > 99 ? '99+' : $cart_count) : '0' }}
                        </span>
                    </a>
                @endif

                @admin
                    <a href="{{ route('admin.dashboard') }}" class=" {{ request()->routeIs('admin.dashboard') ? 'text-blue-400' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>

                    <a href="{{ route('admin.orders.index') }}" class=" {{ request()->routeIs('admin.orders.index') ? 'text-blue-400' : '' }}">
                        Orders
                    </a>

                    <a href="{{ route('products.create') }}" class=" {{ request()->routeIs('products.create') ? 'text-blue-400' : '' }}">
                        <i class="bi bi-plus-circle"></i> Create
                    </a>

                    <a href="{{ route('admin.online-customers') }}" class=" {{ request()->routeIs('admin.online-customers') ? 'text-blue-400' : '' }}">
                        <i class="bi bi-people"></i> Online
                    </a>

                    <a href="{{ route('admin.cache-monitor') }}" class=" {{ request()->routeIs('admin.cache-monitor') ? 'text-blue-400' : '' }}">
                        <i class="bi bi-speedometer"></i> Cache
                    </a>

                    <a href="{{ route('admin.sales-analytics') }}" class=" {{ request()->routeIs('admin.sales-analytics') ? 'text-blue-400' : '' }}">
                        <i class="bi bi-graph-up-arrow"></i> Sales
                    </a>
                @endadmin

                <!-- Auth -->
                @auth
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="border border-red-500 text-red-500 px-3 py-1 rounded hover:bg-red-500 hover:text-white text-sm">
                            Log out
                        </button>
                    </form>
                @endauth

                @guest
                    <a href="{{ route('login') }}"
                       class="border border-gray-300 px-3 py-1 rounded hover:bg-white hover:text-black text-sm">
                        Log in
                    </a>
                @endguest

            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('menu-btn').addEventListener('click', function () {
        document.getElementById('menu').classList.toggle('hidden');
    });
</script>