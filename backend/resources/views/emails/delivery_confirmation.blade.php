<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered - CloudiMart</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            background: white;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
        }
        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .order-info:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .items-list {
            margin: 20px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 10px 10px;
            color: #6c757d;
        }
        .thank-you {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <span style="color: #ffa500;">Cloud</span><span style="color: #4285f4;">iMart</span>
        </div>
        <h1>Order Successfully Delivered!</h1>
    </div>

    <div class="content">
        <div class="success-box">
            <h3>🎉 Great News!</h3>
            <p>Your order has been successfully delivered to your specified location. Thank you for choosing CloudiMart for your shopping needs!</p>
        </div>

        <div class="order-details">
            <h3>Order Information</h3>
            <div class="order-info">
                <span><strong>Order ID:</strong></span>
                <span>{{ $order_id }}</span>
            </div>
            <div class="order-info">
                <span><strong>Delivered To:</strong></span>
                <span>{{ $delivery_address }}</span>
            </div>
            <div class="order-info">
                <span><strong>Delivery Time:</strong></span>
                <span>{{ $delivered_at }}</span>
            </div>
            <div class="order-info">
                <span><strong>Total Amount:</strong></span>
                <span style="color: #28a745; font-weight: bold;">MWK {{ $total_amount }}</span>
            </div>
        </div>

        <h3>Delivered Items</h3>
        <div class="items-list">
            @foreach($items as $item)
            <div class="item">
                <div>
                    <strong>{{ $item->product->name }}</strong><br>
                    <small style="color: #6c757d;">{{ $item->product->category->name }} × {{ $item->quantity }}</small>
                </div>
                <span>MWK {{ number_format($item->price * $item->quantity) }}</span>
            </div>
            @endforeach
            <div class="total">
                Total: MWK {{ $total_amount }}
            </div>
        </div>

        <div class="thank-you">
            Thank you for shopping with CloudiMart! 🛍️
        </div>

        <p style="text-align: center; color: #6c757d;">
            We hope to serve you again soon. For any questions or concerns, please contact our support team.
        </p>
    </div>

    <div class="footer">
        <p><strong>CloudiMart</strong> - Your Trusted Campus Marketplace</p>
        <p>Serving Mzuzu University Community</p>
        <p style="font-size: 12px; margin-top: 10px;">
            This is an automated message. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
