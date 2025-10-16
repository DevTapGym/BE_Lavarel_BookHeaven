<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hóa đơn</title>
    <style>
        * { font-family: Arial, sans-serif; font-size: 12pt; }
        .container { max-width: 720px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; text-align: left; }
        .border-top { border-top: 1px solid #333; margin: 12px 0; }
        .text-right { text-align: right; }
    </style>
    <link rel="icon" href="data:,">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' data:; img-src 'self' data:;">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex" />
</head>
<body>
<div class="container">
    <div class="header">
        <h2>HÓA ĐƠN BÁN HÀNG</h2>
        <div>Mã đơn: {{ $order->order_number }}</div>
        <div>Ngày tạo: {{ $order->created_at }}</div>
    </div>

    <div class="border-top"></div>

    <table>
        <tr>
            <th>Người nhận</th>
            <td>{{ $order->receiver_name }}</td>
        </tr>
        <tr>
            <th>Điện thoại</th>
            <td>{{ $order->receiver_phone }}</td>
        </tr>
        <tr>
            <th>Địa chỉ</th>
            <td>{{ $order->receiver_address }}</td>
        </tr>
        <tr>
            <th>Ghi chú</th>
            <td>{{ $order->note }}</td>
        </tr>
    </table>

    <div class="border-top"></div>

    <table>
        <tr>
            <th>Tổng tiền hàng</th>
            <td class="text-right">{{ number_format($order->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Phí vận chuyển</th>
            <td class="text-right">{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Giảm giá khuyến mãi</th>
            <td class="text-right">- {{ number_format($order->total_promotion_value ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Tổng cộng</th>
            <td class="text-right">{{ number_format(($order->total_amount + ($order->shipping_fee ?? 0)) - ($order->total_promotion_value ?? 0), 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="border-top"></div>
    <div style="text-align:center;">Cảm ơn quý khách!</div>
</div>
</body>
</html>

