<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F5F4F0; margin: 0; padding: 30px 15px; color: #111111; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E8E7E3; border-radius: 8px; overflow: hidden; }
        .header { background: #111111; color: #FFFFFF; padding: 30px; text-align: center; }
        .header h1 { margin: 0 0 8px; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .header p { margin: 0; color: #A3A3A3; font-size: 13px; letter-spacing: 1px; }
        .content { padding: 30px; }
        .order-badge { display: inline-block; background: #F5F4F0; border: 1px solid #E8E7E3; padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: bold; margin-bottom: 20px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th { text-align: left; padding: 10px 0; border-bottom: 2px solid #111111; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        table.items td { padding: 14px 0; border-bottom: 1px solid #E8E7E3; font-size: 14px; vertical-align: top; }
        .totals { margin-top: 25px; border-top: 2px solid #111111; padding-top: 15px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .totals-row.grand { font-size: 18px; font-weight: bold; margin-top: 12px; border-top: 1px dashed #CCCCCC; padding-top: 12px; }
        .address-box { background: #FAF9F5; border: 1px solid #E8E7E3; padding: 18px; border-radius: 6px; margin-top: 25px; font-size: 13px; line-height: 1.6; }
        .footer { background: #FAF9F5; padding: 25px 30px; border-top: 1px solid #E8E7E3; text-align: center; font-size: 12px; color: #737373; }
        .tag { color: #E65100; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>RESET</h1>
            <p>Start fresh. Official Order Confirmation</p>
        </div>
        <div class="content">
            <div class="order-badge">
                Order #{{ $order->number }} • <span class="tag">PAID VIA BAKONG KHQR</span>
            </div>
            
            <p style="font-size: 16px; line-height: 1.5; margin-top: 0;">
                Hello <strong>{{ $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? $order->shipping_address['first_name'] }}</strong>,<br>
                Thank you for your purchase. We have verified your Bakong KHQR transaction and our studio in Phnom Penh is packing your items with care.
            </p>

            <table class="items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product_name }}</strong><br>
                                <span style="font-size: 12px; color: #737373;">{{ $item->variant_label }} (SKU: {{ $item->sku }})</span>
                            </td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">{{ $item->formatted_subtotal }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td style="padding: 4px 0; color: #737373;">Subtotal:</td>
                        <td style="padding: 4px 0; text-align: right;">{{ $order->formatted_subtotal }}</td>
                    </tr>
                    @if($order->discount_cents > 0)
                        <tr>
                            <td style="padding: 4px 0; color: #E65100;">Discount ({{ $order->discount_code }}):</td>
                            <td style="padding: 4px 0; text-align: right; color: #E65100;">{{ $order->formatted_discount }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding: 4px 0; color: #737373;">Delivery Fee:</td>
                        <td style="padding: 4px 0; text-align: right;">{{ $order->formatted_shipping }}</td>
                    </tr>
                    <tr style="font-size: 16px; font-weight: bold; border-top: 1px solid #111111;">
                        <td style="padding: 10px 0 0;">Total Paid:</td>
                        <td style="padding: 10px 0 0; text-align: right;">{{ $order->formatted_total }} ({{ $order->formatted_charged_total }})</td>
                    </tr>
                </table>
            </div>

            <div class="address-box">
                <strong style="text-transform: uppercase; letter-spacing: 1px; font-size: 11px; display: block; margin-bottom: 6px; color: #737373;">Shipping Address</strong>
                {{ $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) }}<br>
                {{ $order->shipping_address['street_address'] }}<br>
                {{ $order->shipping_address['district'] ?? '' }}, {{ $order->shipping_address['province'] }}, Cambodia<br>
                Phone: {{ $order->phone }}
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0 0 5px;">RESET Clothing Co. • Phnom Penh, Cambodia</p>
            <p style="margin: 0;">Have questions? Contact us at support@reset.store or call +855 23 999 123.</p>
        </div>
    </div>
</body>
</html>
