@component('mail::message')
# Xác thực tài khoản

Xin chào,

Mã xác thực của bạn là:

@component('mail::panel')
# {{ $otp }}
@endcomponent

Mã này sẽ hết hạn sau 3 phút.  
Nếu bạn không yêu cầu, vui lòng bỏ qua email này.

Cảm ơn,<br>
{{ config('app.name') }}
@endcomponent
