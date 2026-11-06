<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update #{{ $order->number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #F5F4F0; margin: 0; padding: 30px 15px; color: #111111; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E8E7E3; border-radius: 8px; overflow: hidden; }
        .header { background: #111111; color: #FFFFFF; padding: 25px 30px; text-align: center; }
        .header h1 { margin: 0 0 5px; font-size: 22px; letter-spacing: 2px; }
        .content { padding: 30px; }
        .status-badge { display: inline-block; background: #111111; color: #FFFFFF; padding: 6px 14px; border-radius: 4px; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
        .tracking-box { background: #FAF9F5; border: 1px solid #E8E7E3; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #FAF9F5; padding: 20px 30px; text-align: center; font-size: 12px; color: #737373; border-top: 1px solid #E8E7E3; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>RESET</h1>
            <p style="margin: 0; color: #A3A3A3; font-size: 12px; letter-spacing: 1px;">Order Status Notification</p>
        </div>
        <div class="content">
            <div class="status-badge">
                Status: {{ $order->status->label() }}
            </div>

            <p style="font-size: 16px; margin-top: 0;">
                Hello <strong>{{ $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? $order->shipping_address['first_name'] }}</strong>,
            </p>
            <p style="font-size: 14px; line-height: 1.6; color: #333333;">
                {{ $statusMessage }}
            </p>

            @if($order->tracking_number)
                <div class="tracking-box">
                    <strong style="display: block; font-size: 12px; color: #737373; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Shipping Information</strong>
                    <div style="font-size: 15px; margin-bottom: 4px;"><strong>Carrier:</strong> {{ $order->shipping_carrier ?? 'Standard Express Cambodia' }}</div>
                    <div style="font-size: 15px;"><strong>Tracking Number:</strong> <span style="font-family: monospace; font-size: 16px; color: #E65100;">{{ $order->tracking_number }}</span></div>
                </div>
            @endif

            <p style="font-size: 14px; color: #737373; margin-top: 25px;">
                You can track your order status anytime by visiting our storefront and navigating to your account.
            </p>
        </div>
        <div class="footer">
            <p style="margin: 0;">RESET Store • Phnom Penh, Cambodia • support@reset.store</p>
        </div>
    </div>
</body>
</html>
