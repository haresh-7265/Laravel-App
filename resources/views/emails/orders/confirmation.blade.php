<x-mail::message>
<div style="text-align: center;">
    <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Logo" style="max-height: 60px; margin-bottom: 20px;">
</div>

# {{ __('emails.order.subject') }}

{{ __('emails.order.greeting', ['name' => $order->name]) }}

{!! __('emails.order.received', ['order_number' => $order->order_number]) !!}

### {{ __('emails.order.summary') }}

<x-mail::table>
| {{ __('emails.order.item') }} | {{ __('emails.order.quantity') }} | {{ __('emails.order.price') }} |
| :--- | :---: | ---: |
@foreach($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | {{ format_price($item->price) }} |
@endforeach
| | **{{ __('emails.order.subtotal') }}** | **{{ format_price($order->subtotal) }}** |
@if($order->discount > 0)
| | **{{ __('emails.order.discount') }}** | **-{{ format_price($order->discount) }}** |
@endif
@if($order->coupon_discount > 0)
| | **{{ __('emails.order.coupon', ['code' => $order->coupon_code]) }}** | **-{{ format_price($order->coupon_discount) }}** |
@endif
| | **{{ __('emails.order.total') }}** | **{{ format_price($order->total) }}** |
</x-mail::table>

<x-mail::button :url="$url">
{{ __('emails.order.view_button') }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
