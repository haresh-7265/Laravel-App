@props(['product'])

<div class="group w-full max-w-sm bg-white rounded-2xl border border-gray-100 overflow-hidden transition-transform duration-300 hover:-translate-y-1 font-sans">

    {{-- Image --}}
    <div class="relative h-52 overflow-hidden bg-gray-50">
        <img src="{{ $product->image ? Storage::url($product->image) : Storage::url('products/default.png') }}"
             alt="{{ $product->name }}"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

        {{-- Category Badge --}}
        <span class="absolute top-3 left-3 text-[11px] font-semibold uppercase tracking-wide px-3 py-1 rounded-full bg-blue-50 text-blue-800">
            {{ $product->category_name ?? $product->category?->name }}
        </span>

        {{-- Discount Pill --}}
        @if(!empty($product->discount_price) && $product->discount_price < $product->price)
            <span class="absolute top-3 right-3 text-[11px] font-semibold px-3 py-1 rounded-full bg-green-50 text-green-800">
                {{ round(($product->price - $product->discount_price) / $product->price * 100) }}% off
            </span>
        @endif

        {{-- Out of Stock Overlay --}}
        @if($product->stock <= 0)
            <div class="absolute inset-0 bg-white/50 flex items-center justify-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-red-800">{{ __('products.out_of_stock') }}</span>
            </div>
        @endif

        {{-- {{ image filesize }} --}}
        @can('manage-products')
        <span class="absolute bottom-2 right-2 text-[10px] font-medium px-2 py-0.5 rounded-md bg-black/60 text-white backdrop-blur-sm">
            {{ human_file_size(Storage::disk('public')->size($product->image ?? 'products/default.png')) }}
        </span>
        @endcan
    </div>

    {{-- Body --}}
    <div class="flex flex-col gap-2 p-4">

        {{-- Name --}}
        <h3 class="text-[17px] font-semibold text-gray-900 leading-snug tracking-tight">
            {{ str($product->name)->limit(20, '...', true) }}
        </h3>

        {{-- Description --}}
        <p class="text-[13px] text-gray-400 leading-relaxed line-clamp-2">
            {{ str($product->description ?? __('products.no_description'))->limit(100) }}
        </p>

        {{-- Price --}}
        <div class="flex items-baseline gap-2 mt-1">
            @if(!empty($product->discount_price) && $product->discount_price < $product->price)
                <span class="text-[13px] text-gray-300 line-through">@currency($product->price)</span>
                <span class="text-[22px] font-semibold text-gray-900 tracking-tight">@currency($product->discount_price)</span>
            @else
                <span class="text-[22px] font-semibold text-gray-900 tracking-tight">@currency($product->price)</span>
            @endif
        </div>

        {{-- Divider --}}
        <div class="border-t border-gray-100 my-1"></div>

        {{-- Stock Status --}}
        <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $product->stock > 0 ? 'bg-green-500' : 'bg-red-400' }}"></span>
            <span class="text-[12px] font-medium {{ $product->stock > 0 ? 'text-green-800' : 'text-red-700' }}">
                {{ $product->stock > 0 ? __('products.in_stock', ['count' => $product->stock]) : __('products.out_of_stock') }}
            </span>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2 mt-1">

            {{-- ═══ ADMIN ═══ --}}
            @admin
                <a href="{{ route('products.show', $product->slug) }}"
                   class="flex-1 text-center text-[13px] font-medium py-2 rounded-xl bg-gray-900 text-white transition-opacity hover:opacity-80">
                    {{ __('products.view') }}
                </a>

            {{-- ═══ CUSTOMER (authenticated) ═══ --}}
            @elseif(is_customer())
                @if($product->stock > 0)
                    <a href="{{ route('products.show', $product->slug) }}"
                       class="flex-1 text-center text-[13px] font-medium py-2 rounded-xl bg-gray-900 text-white transition-opacity hover:opacity-80">
                        {{ __('products.view') }}
                    </a>
                    <form action="{{ route('cart.add', $product->slug) }}"
                          method="POST"
                          class="ajax-add-to-cart-form flex-1">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit"
                                class="w-full text-center text-[13px] font-medium py-2 rounded-xl bg-emerald-600 text-white transition-opacity hover:opacity-90">
                            {{ __('products.add_to_cart') }}
                        </button>
                    </form>
                @else
                    @php $onWaitlist = current_user()->waitlistProducts()->where('product_id', $product->id)->exists(); @endphp

                    @if($onWaitlist)
                        <button type="button"
                                class="waitlist-remove-btn flex-1 text-center text-[13px] font-medium py-2 rounded-xl bg-violet-100 text-violet-700 border border-violet-200 transition-opacity hover:opacity-90"
                                data-store-url="{{ route('product.waitlist.store', $product->slug) }}"
                                data-destroy-url="{{ route('product.waitlist.destroy', $product->slug) }}">
                            ✓ {{ __('products.remove_notify') }}
                        </button>
                    @else
                        <button type="button"
                                class="waitlist-btn flex-1 text-center text-[13px] font-medium py-2 rounded-xl bg-violet-600 text-white transition-opacity hover:opacity-90"
                                data-store-url="{{ route('product.waitlist.store', $product->slug) }}"
                                data-destroy-url="{{ route('product.waitlist.destroy', $product->slug) }}">
                            🔔 {{ __('products.notify_me') }}
                        </button>
                    @endif
                @endif

            {{-- ═══ GUEST ═══ --}}
            @else
                <a href="{{ route('products.show', $product->slug) }}"
                   class="flex-1 text-center text-[13px] font-medium py-2 rounded-xl bg-gray-900 text-white transition-opacity hover:opacity-80 {{ $product->stock <= 0 ? 'opacity-40 pointer-events-none' : '' }}">
                    {{ __('products.view') }}
                </a>
                @if($product->stock > 0)
                    <form action="{{ route('cart.add', $product->slug) }}"
                          method="POST"
                          class="ajax-add-to-cart-form flex-1">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit"
                                class="w-full text-center text-[13px] font-medium py-2 rounded-xl bg-emerald-600 text-white transition-opacity hover:opacity-90">
                            {{ __('products.add_to_cart') }}
                        </button>
                    </form>
                @endif
            @endadmin

            @can('manage-products')
                <a href="{{ route('products.edit', $product->slug) }}"
                   class="flex-1 text-center text-[13px] font-medium py-2 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 hover:bg-amber-100 transition-colors">
                    {{ __('products.edit') }}
                </a>
            @endcan

            @can('manage-products')
                <form action="{{ route('products.destroy', $product->slug) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('products.delete_short') }}')"
                      style="display:contents;">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-10 text-[13px] font-medium rounded-xl bg-red-50 text-red-700 border border-red-100 hover:bg-red-100 transition-colors">
                        ✕
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>
