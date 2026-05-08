<x-mail::message>
# Low Stock Alert

The following product has fallen below the low-stock threshold.

<x-mail::table>
| Product ID | Product Name | Current Stock |
| :--- | :--- | :---: |
| {{ $product->id }} | {{ $product->name }} | {{ $product->stock }} |
</x-mail::table>

<x-mail::button :url="route('products.edit', $product)">
View Product
</x-mail::button>

Please restock this item soon to avoid it going out of stock.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
