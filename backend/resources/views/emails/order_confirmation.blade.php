<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - CloudiMart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .order-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .item {
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 0;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            color: #3b82f6;
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛒 Order Confirmation</h1>
        <p>Thank you for shopping with CloudiMart!</p>
    </div>

    <div class="content">
        <p>Dear {{ $customer_name }},</p>
        <p>Your order has been successfully placed and is now being processed. Here are your order details:</p>

        <div class="order-info">
            <h3>Order Information</h3>
            <p><strong>Order ID:</strong> {{ $order_id }}</p>
            <p><strong>Order Date:</strong> {{ $created_at }}</p>
            <p><strong>Total Amount:</strong> MWK {{ $total_amount }}</p>
        </div>

        <div class="order-info">
            <h3>📍 Delivery Information</h3>
            <p><strong>Location:</strong> {{ $delivery_location }}</p>
            <p><strong>Address:</strong> {{ $delivery_address }}</p>
            <p><strong>Phone:</strong> {{ $phone }}</p>
        </div>

        <div class="order-info">
            <h3>📦 Order Items</h3>
            @foreach($items as $item)
                <div class="item">
                    <p><strong>{{ $item->product->name }}</strong></p>
                    <p>Category: {{ $item->product->category->name }}</p>
                    <p>Quantity: {{ $item->quantity }} × MWK {{ number_format($item->price) }} = MWK {{ number_format($item->quantity * $item->price) }}</p>
                </div>
            @endforeach
            
            <div class="total">
                Total: MWK {{ $total_amount }}
            </div>
        </div>

        <div class="order-info" style="background: #fef3c7; border-left-color: #f59e0b;">
            <h3>⚠️ Important Information</h3>
            <p>Please save your Order ID: <strong>{{ $order_id }}</strong></p>
            <p>You'll need to show this to the delivery person when they arrive.</p>
            <p>Our delivery team will contact you at {{ $phone }} when they're on their way.</p>
        </div>

        <p>If you have any questions or need to make changes to your order, please contact our customer service.</p>
    </div>

    <div class="footer">
        <p><strong>CloudiMart</strong></p>
        <p>Your trusted delivery service</p>
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
