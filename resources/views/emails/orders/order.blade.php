<x-mail::message>

@switch($event)
    @case('placed')
        # Order Placed 🛍️
        Your order #{{ $order->order_number }} has been placed.
    @break

    @case('shipped')
        # Order Shipped 🚚
        Order #{{ $order->order_number }} is on the way!
        @if($order->tracking_number)
        **Tracking:** {{ $order->tracking_number }}
        @endif
    @break

    @case('paid')
        # Payment Confirmed ✅
        Payment of @currency($order->total) received.
    @break

    @case('delivered')
        # Order Delivered 📦
        Order #{{ $order->order_number }} delivered!
    @break
@endswitch

<x-mail::button :url="route('orders.show', $order)">
View Order
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>