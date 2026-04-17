<x-mail::message>
# Hey {{ $user->name }}, you forgot something!

You left {{ $cartItems->count() }} item(s) in your cart.

<x-mail::table>
| Product | Qty | Price |
|:--------|:----|------:|
@foreach($cartItems as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | @currency($item->price) |
@endforeach
</x-mail::table>

**Total: @currency($cartTotal)**

<x-mail::button :url="route('cart.index')">
Complete Your Purchase
</x-mail::button>

Hurry! Items in your cart are not reserved.

Thanks,
{{ config('app.name') }}
</x-mail::message>