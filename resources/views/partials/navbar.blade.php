<nav class="bg-gray-900 text-white shadow sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">

        <div class="flex justify-between items-center h-16">

            <!-- Logo -->
            <a href="/" class="text-lg font-semibold">
                {{ config('admin.name') }}
            </a>

            <!-- Mobile Toggle -->
            <button id="menu-btn" class="lg:hidden text-white">
                <i class="bi bi-list text-2xl"></i>
            </button>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-5">

                <a href="{{ route('products.index') }}" class="hover:text-gray-300">
                    Products
                </a>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('orders.index') }}" class="hover:text-gray-300">
                            My Orders
                        </a>
                    @endif
                @endauth

                @if(!auth()->check() || auth()->user()->role === 'customer')
                    <!-- Cart -->
                    <a href="{{ route('cart.index') }}" class="relative">
                        <i class="bi bi-cart text-xl"></i>
                        <span id="cart-badge"
                            class="absolute -top-2 -right-3 bg-red-500 text-xs px-1.5 py-0.5 rounded-full">
                            {{ $cart_count > 0 ? ($cart_count > 99 ? '99+' : $cart_count) : '0' }}
                        </span>
                    </a>
                @endif

                @admin
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-1 hover:text-gray-300">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>

                    <a href="{{ route('admin.orders.index') }}" class="hover:text-gray-300">
                        Orders
                    </a>

                    <a href="{{ route('products.create') }}" class="flex items-center gap-1 hover:text-gray-300">
                        <i class="bi bi-plus-circle"></i> Create
                    </a>

                    <a href="{{ route('admin.online-customers') }}" class="flex items-center gap-1 hover:text-gray-300">
                        <i class="bi bi-people"></i> Online
                    </a>

                    <a href="{{ route('admin.cache-monitor') }}" class="flex items-center gap-1 hover:text-gray-300">
                        <i class="bi bi-speedometer"></i> Cache
                    </a>

                    <a href="{{ route('admin.sales-analytics') }}"
                        class="flex items-center gap-1 {{ request()->routeIs('admin.sales-analytics') ? 'text-blue-400' : 'hover:text-gray-300' }}">
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

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden lg:hidden flex-col space-y-3 py-4 border-t border-gray-700">

            <a href="{{ route('products.index') }}" class="block">Products</a>

            @auth
                @if(auth()->user()->role === 'customer')
                    <a href="{{ route('orders.index') }}" class="block">My Orders</a>
                @endif
            @endauth

            @if(!auth()->check() || auth()->user()->role === 'customer')
                <a href="{{ route('cart.index') }}" class="flex items-center gap-2">
                    <i class="bi bi-cart"></i> Cart
                </a>
            @endif

            @admin
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <a href="{{ route('admin.orders.index') }}" class="block">Orders</a>

                <a href="{{ route('products.create') }}" class="flex items-center gap-2">
                    <i class="bi bi-plus-circle"></i> Create
                </a>

                <a href="{{ route('admin.online-customers') }}" class="flex items-center gap-2">
                    <i class="bi bi-people"></i> Online Customers
                </a>

                <a href="{{ route('admin.cache-monitor') }}" class="flex items-center gap-2">
                    <i class="bi bi-speedometer"></i> Cache
                </a>

                <a href="{{ route('admin.sales-analytics') }}" class="flex items-center gap-2">
                    <i class="bi bi-graph-up-arrow"></i> Sales
                </a>
            @endadmin

            @auth
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="text-red-500">Log out</button>
                </form>
            @endauth

            @guest
                <a href="{{ route('login') }}">Log in</a>
            @endguest

        </div>

    </div>
</nav>

<script>
    document.getElementById('menu-btn').addEventListener('click', function () {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>