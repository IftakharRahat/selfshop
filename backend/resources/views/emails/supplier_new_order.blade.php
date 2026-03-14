<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Notification</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#2d2a5d; padding: 24px 30px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:700;">
                                New Order Received – Action Required
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 30px;">
                            <p style="margin:0 0 16px; color:#333; font-size:15px; line-height:1.6;">
                                Dear <strong>{{ $vendor->company_name }}</strong>,
                            </p>
                            <p style="margin:0 0 20px; color:#555; font-size:14px; line-height:1.6;">
                                Greetings from <strong>SelfShop Limited</strong>.
                            </p>
                            <p style="margin:0 0 20px; color:#555; font-size:14px; line-height:1.6;">
                                You have received a new order on our platform. Please review the order details below and process it as soon as possible.
                            </p>

                            <!-- Order Details -->
                            <h3 style="margin:0 0 10px; color:#2d2a5d; font-size:15px;">Order Details</h3>
                            <table width="100%" cellpadding="10" cellspacing="0" style="margin-bottom:20px; border:1px solid #e5e7eb; border-radius:6px; border-collapse:collapse;">
                                <tr style="background-color:#f9fafb;">
                                    <td style="font-size:13px; color:#6b7280; border-bottom:1px solid #e5e7eb; font-weight:600; width:35%;">Order ID</td>
                                    <td style="font-size:13px; color:#111; border-bottom:1px solid #e5e7eb; font-weight:700;">{{ $order->invoiceID }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size:13px; color:#6b7280; border-bottom:1px solid #e5e7eb; font-weight:600;">Order Date</td>
                                    <td style="font-size:13px; color:#111; border-bottom:1px solid #e5e7eb;">{{ $order->orderDate ?? ($order->created_at ? date('d M Y', strtotime($order->created_at)) : 'N/A') }}</td>
                                </tr>
                            </table>

                            <!-- Products -->
                            <h3 style="margin:0 0 10px; color:#2d2a5d; font-size:15px;">Products in This Order</h3>
                            <table width="100%" cellpadding="10" cellspacing="0" style="margin-bottom:24px; border:1px solid #e5e7eb; border-radius:6px; border-collapse:collapse;">
                                <thead>
                                    <tr style="background-color:#f3f4f6;">
                                        <th style="text-align:left; font-size:12px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Product</th>
                                        <th style="text-align:center; font-size:12px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orderProducts as $item)
                                    <tr>
                                        <td style="font-size:13px; color:#111; border-bottom:1px solid #f3f4f6;">{{ $item->productName }}</td>
                                        <td style="font-size:13px; color:#111; border-bottom:1px solid #f3f4f6; text-align:center;">{{ $item->quantity }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- CTA -->
                            <p style="margin:0 0 20px; color:#555; font-size:14px; line-height:1.6;">
                                Please log in to your <strong>Supplier Dashboard</strong> to review the full order details and start processing the order.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ env('FRONTEND_URL', 'https://selfshop.com.bd') }}/vendor/orders"
                                           style="display:inline-block; padding:12px 32px; background-color:#2d2a5d; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; border-radius:6px;">
                                            Go to Supplier Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px; color:#555; font-size:14px; line-height:1.6;">
                                If you have any questions or need assistance, please feel free to contact our support team.
                            </p>

                            <!-- Closing -->
                            <p style="margin:0 0 4px; color:#555; font-size:14px; line-height:1.6;">
                                Thank you for partnering with <strong>SelfShop Limited</strong>.
                            </p>
                            <p style="margin:0 0 4px; color:#333; font-size:14px; line-height:1.6;">
                                Best regards,
                            </p>
                            <p style="margin:0 0 4px; color:#2d2a5d; font-size:15px; font-weight:700; line-height:1.6;">
                                SelfShop Limited
                            </p>
                            <p style="margin:0; color:#9ca3af; font-size:12px; font-style:italic; line-height:1.6;">
                                Empowering Bangladesh's Entrepreneurs with Reliable Wholesale & Dropshipping Solutions
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb; padding: 20px 30px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; color:#9ca3af; font-size:12px;">
                                &copy; {{ date('Y') }} SelfShop Limited. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
