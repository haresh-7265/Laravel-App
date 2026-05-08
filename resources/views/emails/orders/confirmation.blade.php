<x-mail::message>
# Order Confirmation

Thank you for your order, {{ $order->name }}!

Your order **#{{ $order->order_number }}** has been received and is currently being processed.

### Order Summary

<x-mail::table>
| Item | Quantity | Price |
| :--- | :---: | ---: |
@foreach($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | {{ format_price($item->price) }} |
@endforeach
| | **Subtotal** | **{{ format_price($order->subtotal) }}** |
@if($order->discount > 0)
| | **Discount** | **-{{ format_price($order->discount) }}** |
@endif
@if($order->coupon_discount > 0)
| | **Coupon ({{ $order->coupon_code }})** | **-{{ format_price($order->coupon_discount) }}** |
@endif
| | **Total** | **{{ format_price($order->total) }}** |
</x-mail::table>

<x-mail::button :url="$url">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
