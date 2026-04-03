{{-- resources/views/cart/_item.blade.php --}}
<div class="cart-item p-3" id="cart-item-{{ $item['product_id'] }}">
    <div class="d-flex align-items-center gap-3 flex-wrap flex-sm-nowrap">

        {{-- Product Image --}}
        <div class="item-img-wrap">
            @if(!empty($item['image_url']))
                <img src="{{ $item['image_url'] }}"
                     alt="{{ $item['name'] }}"
                     onerror="this.style.display='none'">
            @else
                <i class="bi bi-box-seam text-secondary" style="font-size:22px"></i>
            @endif
        </div>

        {{-- Product Info --}}
        <div class="flex-grow-1 min-width-0">
            <div class="fw-semibold text-truncate mb-1">{{ $item['name'] }}</div>

            <div class="text-muted small">
                @if(!empty($item['original_price']) && $item['original_price'] > $item['price'])
                    <span class="text-decoration-line-through me-1">@currency($item['original_price'])</span>
                    <strong class="text-success">@currency($item['price'])</strong>
                    <span class="badge bg-success ms-1" style="font-size:10px;">
                        {{ round((($item['original_price'] - $item['price']) / $item['original_price']) * 100) }}% OFF
                    </span>
                @else
                    <strong class="text-dark">@currency($item['price'])</strong>
                @endif
            </div>

            @if($item['stock'] <= 5 && $item['stock'] > 0)
                <div class="text-warning small mt-1">
                    <i class="bi bi-exclamation-circle me-1"></i>Only {{ $item['stock'] }} left
                </div>
            @elseif($item['stock'] === 0)
                <div class="text-danger small mt-1">
                    <i class="bi bi-x-circle me-1"></i>Out of stock
                </div>
            @endif
        </div>

        {{-- Qty Controls (data-attributes drive AJAX) --}}
        <div class="d-flex align-items-center gap-1 flex-shrink-0">
            <button class="btn btn-outline-secondary qty-btn"
                    data-action="qty"
                    data-url="{{ route('cart.update', $item['product_id']) }}"
                    data-qty="{{ $item['quantity'] - 1 }}"
                    title="Decrease"
                    {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                <i class="bi bi-dash"></i>
            </button>

            <span class="qty-display">{{ $item['quantity'] }}</span>

            <button class="btn btn-outline-secondary qty-btn"
                    data-action="qty"
                    data-url="{{ route('cart.update', $item['product_id']) }}"
                    data-qty="{{ $item['quantity'] + 1 }}"
                    title="Increase"
                    {{ $item['quantity'] >= $item['stock'] ? 'disabled' : '' }}>
                <i class="bi bi-plus"></i>
            </button>
        </div>

        {{-- Line Subtotal --}}
        <div class="text-end flex-shrink-0" style="min-width:90px;">
            @if(!empty($item['original_price']) && $item['original_price'] > $item['price'])
                <div class="text-decoration-line-through text-muted small">
                    @currency($item['original_price'] * $item['quantity'])
                </div>
                <div class="fw-bold text-success">@currency($item['subtotal'])</div>
            @else
                <div class="fw-bold text-dark">@currency($item['subtotal'])</div>
            @endif
            <div class="text-muted small">{{ $item['quantity'] }} × @currency($item['price'])</div>
        </div>

        {{-- Remove --}}
        <button class="btn btn-sm btn-outline-danger flex-shrink-0"
                data-action="remove"
                data-url="{{ route('cart.remove', $item['product_id']) }}"
                data-name="{{ $item['name'] }}"
                title="Remove item">
            <i class="bi bi-trash3"></i>
        </button>

    </div>
</div>