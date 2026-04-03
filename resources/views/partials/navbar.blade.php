<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-semibold" href="/">
            {{ config('admin.name') }}
        </a>

        <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('products.index') }}">Products</a>
                </li>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('orders.index') }}">My Orders</a>
                        </li>
                    @endif
                @endauth

                @if(!auth()->check() || auth()->user()->role === 'customer')
                    <li class="nav-item">
                        <a class="nav-link px-3" href="{{ route('cart.index') }}">
                            <span class="position-relative d-inline-block">
                                <i class="bi bi-cart fs-5"></i>
                            
                                    <span
                                    id="cart-badge" 
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    style="font-size: 0.65rem;">
                                        {{ $cart_count > 0 ? ($cart_count > 99 ? '99+' : $cart_count) : '0' }}
                                    </span>
                            </span>
                        </a>
                    </li>
                @endif

                @admin
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.orders.index') }}">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('products.create') }}">
                            <i class="bi bi-plus-circle me-1"></i>Create
                        </a>
                    </li>
                @endadmin

                <li class="nav-item ms-lg-2">
                    @auth
                        <form action="{{ route('logout') }}" method="POST" class="d-inline m-0">
                            @csrf
                            <button type="submit"
                                class="btn btn-sm btn-outline-danger px-3">
                                Log out
                            </button>
                        </form>
                    @endauth

                    @guest
                        <a href="{{ route('login') }}"
                            class="btn btn-sm btn-outline-light px-3">
                            Log in
                        </a>
                    @endguest
                </li>

            </ul>
        </div>

    </div>
</nav>