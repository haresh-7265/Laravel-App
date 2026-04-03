{{-- resources/views/cart/_items_loop.blade.php --}}
{{-- Renders all cart-item rows; used only in the AJAX JSON response --}}
@foreach ($items as $item)
    @include('cart._item', ['item' => $item])
@endforeach