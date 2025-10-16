<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <style>
        * { font-size: 10pt; font-family: 'DejaVu Sans', sans-serif; }

        @page { size: 80mm auto; margin: 6px 6px 6px 0px; }
        html, body { margin: 0; padding: 0; }

        td, th, tr, table { border-collapse: collapse; width: 100%; max-width: 70mm; }

        td.name, th.name { word-break: break-word; text-align: left; font-weight: 400; }

        td.qty, th.qty { width: 10mm !important; max-width: 10mm !important; }
        td.price, th.price { width: 15mm; max-width: 15mm; }
        .pname { width: 20mm; }
        td.total, th.total { width: 15mm; max-width: 15mm; }

        .centered { text-align: center; align-content: center; }
        .ticket { width: 62mm; max-width: 62mm; margin: 0 auto; }

        img { max-width: inherit; width: inherit; }

        .text-right { text-align: right; }
        .border-top { border-top: 0.1mm solid #000; }
        table tr.pt-3 td { padding-top: 10px; }
        .ver-top { vertical-align: auto; }
    </style>
</head>
<body>
<div class="ticket">
    <p class="centered">
        <br/><b style="font-size: 11pt">HÓA ĐƠN BÁN HÀNG</b>
        <br/><span>Mã đơn: {{ $order->order_number }}</span>
        <br/><span>Ngày tạo: {{ $order->created_at }}</span>
    </p>

    <div class="border-top"></div>

    <table class="ticket">
        <tr>
            <td colspan="4">Khách hàng: {{ $order->receiver_name }}</td>
        </tr>
        <tr>
            <td colspan="4">SĐT: {{ $order->receiver_phone }}</td>
        </tr>
        <tr>
            <td colspan="4">Địa chỉ: {{ $order->receiver_address }}</td>
        </tr>
    </table>

    <div class="border-top"></div>

    <div class="ticket">
        <table>
            <tbody>
            <tr>
                <td class="price text-left">Đơn giá</td>
                <td class="qty centered">SL</td>
                <td class="total text-right">Tổng tiền</td>
            </tr>

            @foreach(($order->orderItems ?? []) as $index => $item)
                <tr style="margin-bottom: 10px">
                    <td class="pname" style="font-weight: 400;" colspan="3">
                        {{ $index + 1 }}. {{ $item->book->title ?? ('Sách #'.$item->book_id) }}
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 400; padding-left: 10px" class="price text-left ver-top pname">
                        {{ number_format($item->price, 0, ',', '.') }}
                    </td>
                    <td style="font-weight: 400" class="qty centered ver-top pname">
                        {{ $item->quantity }}
                    </td>
                    <td style="font-weight: 400" class="total text-right ver-top pname">
                        {{ number_format($item->price * $item->quantity, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            </tbody>
        </table>

        <table class="border-top">
            <tr class="border-top">
                <th class="name" style="font-size: 12pt;">Thành tiền</th>
                <th colspan="1" class="text-right" style="font-size: 12pt; font-weight: 400;white-space: nowrap;">
                    {{ number_format($order->orderItems->sum(function($item) { return $item->price * $item->quantity; }), 0, ',', '.') }}
                </th>
            </tr>
            <tr>
                <th class="name" style="font-size: 12pt;">Phí vận chuyển</th>
                <th colspan="1" class="text-right" style="font-size: 12pt; font-weight: 400;white-space: nowrap;">
                    {{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }}
                </th>
            </tr>
            <tr>
                <th class="name" style="font-size: 12pt;">Khuyến mãi</th>
                <th colspan="1" class="text-right" style="font-size: 12pt; font-weight: 400;white-space: nowrap;">
                    - {{ number_format($order->total_promotion_value ?? 0, 0, ',', '.') }}
                </th>
            </tr>
            <tr class="border-top">
                <th class="name" style="font-size: 12pt;">Tổng cộng</th>
                <th colspan="1" class="text-right" style="font-size: 12pt; font-weight: 400; white-space: nowrap;">
                    {{ number_format(($order->total_amount), 0, ',', '.') }}
                </th>
            </tr>
            @if($order->note)
            <tr>
                <td colspan="2" class="text-left" style="font-size: 12pt;">Ghi chú: {{ $order->note }}</td>
            </tr>
            @endif
        </table>

        <div class="border-top"></div>
        @if(!empty($qrImg))
        <div style="display:flex; justify-content:center; margin: 8px 0;">
            <img src="{{ $qrImg }}" alt="QR" style="height: 60mm; width: auto;" />
        </div>
        @endif
        <div style="margin: 6pt; text-align: center; font-weight: 500">Cảm ơn quý khách và hẹn gặp lại</div>
    </div>
</div>
</body>
</html>

