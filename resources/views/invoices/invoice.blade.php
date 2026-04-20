<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #2c2c2c;
            background: #eceae4;
        }

        .page {
            max-width: 820px;
            margin: 0 auto;
            background: #fdfcf9;
            min-height: 100vh;
        }

        /* ── TOP STRIPE ── */
        .top-stripe {
            height: 6px;
            background: #2c2c2c;
        }

        .top-stripe-accent {
            height: 3px;
            background: #c8a96e;
        }

        /* ── HEADER ── */
        .header {
            padding: 50px 60px 40px;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 50%;
        }

        .brand-name {
            font-size: 30px;
            font-weight: 700;
            color: #2c2c2c;
            letter-spacing: 5px;
            text-transform: uppercase;
        }

        .brand-tagline {
            font-size: 10px;
            color: #c8a96e;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .invoice-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 8px;
        }

        .invoice-num {
            font-size: 28px;
            font-weight: 700;
            color: #2c2c2c;
            letter-spacing: 1px;
        }

        .invoice-date {
            font-size: 11px;
            color: #aaa;
            margin-top: 6px;
            letter-spacing: 1px;
        }

        /* ── GOLD RULE ── */
        .gold-rule {
            margin: 0 60px;
            border: none;
            border-top: 1px solid #c8a96e;
        }

        /* ── ADDRESS BLOCK ── */
        .address-block {
            padding: 34px 60px;
            display: table;
            width: 100%;
        }

        .addr-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .addr-col.right {
            text-align: right;
        }

        .addr-eyebrow {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #c8a96e;
            margin-bottom: 12px;
        }

        .addr-name {
            font-size: 15px;
            font-weight: 700;
            color: #2c2c2c;
            margin-bottom: 5px;
        }

        .addr-line {
            font-size: 12px;
            color: #777;
            line-height: 1.8;
        }

        /* ── ITEMS ── */
        .items-wrap {
            padding: 0 60px;
        }

        .items-eyebrow {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #c8a96e;
            margin-bottom: 14px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead tr {
            border-top: 1px solid #2c2c2c;
            border-bottom: 1px solid #2c2c2c;
        }

        .items-table thead th {
            padding: 10px 12px;
            font-size: 9px;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 700;
            color: #2c2c2c;
            text-align: left;
        }

        .items-table thead th.right {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #ede9e0;
        }

        .items-table tbody td {
            padding: 16px 12px;
            font-size: 13px;
            color: #444;
            vertical-align: top;
        }

        .items-table tbody td.right {
            text-align: right;
        }

        .prod-name {
            font-weight: 700;
            color: #2c2c2c;
            font-size: 13px;
        }

        .orig-price {
            font-size: 11px;
            color: #bbb;
            text-decoration: line-through;
        }

        .disc-price {
            font-size: 13px;
            font-weight: 700;
            color: #7a9e6e;
        }

        /* ── TOTALS ── */
        .totals-section {
            padding: 30px 60px 0;
            display: table;
            width: 100%;
        }

        .totals-spacer {
            display: table-cell;
            width: 55%;
        }

        .totals-box {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        .totals-inner {
            border-top: 1px solid #2c2c2c;
        }

        .totals-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #ede9e0;
        }

        .totals-row-inner {
            display: table-row;
        }

        .totals-label {
            display: table-cell;
            padding: 10px 0;
            font-size: 12px;
            color: #888;
            letter-spacing: 1px;
        }

        .totals-value {
            display: table-cell;
            padding: 10px 0;
            font-size: 12px;
            font-weight: 700;
            color: #2c2c2c;
            text-align: right;
        }

        .totals-value.green {
            color: #7a9e6e;
        }

        .total-final {
            display: table;
            width: 100%;
            background: #2c2c2c;
            margin-top: 2px;
        }

        .total-final-row {
            display: table-row;
        }

        .total-final-label {
            display: table-cell;
            padding: 14px 16px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #fff;
        }

        .total-final-value {
            display: table-cell;
            padding: 14px 16px;
            font-size: 18px;
            font-weight: 700;
            color: #c8a96e;
            text-align: right;
        }

        /* ── NOTES ── */
        .notes-wrap {
            padding: 30px 60px 0;
        }

        .notes-inner {
            padding: 16px 20px;
            background: #f5f2ea;
            border-left: 2px solid #c8a96e;
        }

        .notes-eyebrow {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #c8a96e;
            margin-bottom: 6px;
        }

        .notes-text {
            font-size: 12px;
            color: #777;
            line-height: 1.7;
        }

        /* ── FOOTER ── */
        .footer-wrap {
            padding: 50px 60px 0;
        }

        .footer-rule {
            border: none;
            border-top: 1px solid #ede9e0;
            margin-bottom: 24px;
        }

        .footer-grid {
            display: table;
            width: 100%;
            padding-bottom: 40px;
        }

        .footer-col {
            display: table-cell;
            vertical-align: middle;
            width: 33.33%;
        }

        .footer-col.center {
            text-align: center;
        }

        .footer-col.right {
            text-align: right;
        }

        .footer-label {
            font-size: 8px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ccc;
            margin-bottom: 4px;
        }

        .footer-value {
            font-size: 11px;
            color: #888;
        }

        .footer-brand {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #2c2c2c;
        }

        .footer-tagline {
            font-size: 9px;
            color: #c8a96e;
            letter-spacing: 2px;
            margin-top: 3px;
        }

        /* ── BOTTOM STRIPE ── */
        .bottom-stripe-accent {
            height: 3px;
            background: #c8a96e;
        }

        .bottom-stripe {
            height: 6px;
            background: #2c2c2c;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── TOP STRIPE ── --}}
    <div class="top-stripe"></div>
    <div class="top-stripe-accent"></div>

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="header-left">
            <div class="brand-name">YourShop</div>
            <div class="brand-tagline">Premium Store</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">Invoice</div>
            <div class="invoice-num">#{{ $order->order_number }}</div>
            <div class="invoice-date">{{ $order->created_at->format('d F, Y') }}</div>
        </div>
    </div>

    <hr class="gold-rule">

    {{-- ── ADDRESSES ── --}}
    <div class="address-block">
        <div class="addr-col">
            <div class="addr-eyebrow">Billed To</div>
            <div class="addr-name">{{ $order->user->name }}</div>
            <div class="addr-line">{{ $order->user->email }}</div>
        </div>
        <div class="addr-col right">
            <div class="addr-eyebrow">Shipped To</div>
            <div class="addr-name">{{ $order->shipping_name }}</div>
            <div class="addr-line">{{ $order->shipping_phone }}</div>
            <div class="addr-line">{{ $order->shipping_address }}</div>
            <div class="addr-line">{{ $order->shipping_city }}, {{ $order->shipping_state }} – {{ $order->shipping_pincode }}</div>
            <div class="addr-line">{{ $order->shipping_email }}</div>
        </div>
    </div>

    {{-- ── ITEMS TABLE ── --}}
    <div class="items-wrap">
        <div class="items-eyebrow">Order Summary</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th>Item</th>
                    <th class="right" style="width:130px;">Unit Price</th>
                    <th class="right" style="width:60px;">Qty</th>
                    <th class="right" style="width:110px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $i => $item)
                <tr>
                    <td style="color:#ccc; font-size:11px;">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        <div class="prod-name">{{ $item->product_name }}</div>
                    </td>
                    <td class="right">
                        @if($item->discount_price)
                            <div class="orig-price">@currency($item->price)</div>
                            <div class="disc-price">@currency($item->discount_price)</div>
                        @else
                            @currency($item->price)
                        @endif
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right" style="font-weight:700; color:#2c2c2c;">@currency($item->subtotal)</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── TOTALS ── --}}
    <div class="totals-section">
        <div class="totals-spacer"></div>
        <div class="totals-box">
            <div class="totals-inner">
                <div class="totals-row">
                    <div class="totals-row-inner">
                        <div class="totals-label">Subtotal</div>
                        <div class="totals-value">@currency($order->subtotal)</div>
                    </div>
                </div>
                @if($order->discount > 0)
                <div class="totals-row">
                    <div class="totals-row-inner">
                        <div class="totals-label" style="color:#7a9e6e;">Discount</div>
                        <div class="totals-value green">– @currency($order->discount)</div>
                    </div>
                </div>
                @endif
            </div>
            <div class="total-final">
                <div class="total-final-row">
                    <div class="total-final-label">Total</div>
                    <div class="total-final-value">@currency($order->total)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── NOTES ── --}}
    @if($order->notes)
    <div class="notes-wrap">
        <div class="notes-inner">
            <div class="notes-eyebrow">Notes</div>
            <div class="notes-text">{{ $order->notes }}</div>
        </div>
    </div>
    @endif

    {{-- ── FOOTER ── --}}
    <div class="footer-wrap">
        <hr class="footer-rule">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-label">Support</div>
                <div class="footer-value">support@yourshop.com</div>
            </div>
            <div class="footer-col center">
                <div class="footer-brand">YourShop</div>
                <div class="footer-tagline">Thank you for your order</div>
            </div>
            <div class="footer-col right">
                <div class="footer-label">Generated</div>
                <div class="footer-value">{{ now()->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- ── BOTTOM STRIPE ── --}}
    <div class="bottom-stripe-accent"></div>
    <div class="bottom-stripe"></div>

</div>
</body>
</html>