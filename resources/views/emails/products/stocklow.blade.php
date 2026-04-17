<!-- resources/views/emails/stock/low.blade.php -->

@component('mail::message')
# ⚠️ Low Stock Alert

The following product is running low on stock:

@component('mail::panel')
**Product:** {{ $product->name }}  
**Current Stock:** {{ $product->stock }}  
**Price:** ₹{{ number_format($product->price, 2) }}
@endcomponent

@component('mail::button', ['url' => route('products.edit', $product)])
View Product
@endcomponent

Please restock this item soon to avoid it going out of stock.

Thanks,  
{{ config('app.name') }}
@endcomponent