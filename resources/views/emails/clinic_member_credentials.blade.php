<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
    <style>
        body { font-family: Arial, sans-serif; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
        .email-container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,.1); }
        .header { text-align: center; padding-bottom: 20px; }
        .header img { max-width: 150px; }
        .content { padding: 20px; line-height: 1.6; }
        .content h2 { color: #109014; margin-top: 0; }
        .credentials { background: #f8f9fb; border: 1px solid #e7ebf2; border-radius: 8px; padding: 12px 16px; margin: 16px 0; }
        .credentials-row { margin: 4px 0; }
        .credentials-label { color: #6b7280; font-size: 13px; }
        .credentials-value { font-family: Consolas, Monaco, monospace; font-size: 15px; word-break: break-all; }
        .footer { text-align: center; padding: 20px; background-color: #f8f9fa; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ asset('assets/img/noraya-logo.png') }}" alt="{{ config('app.name') }}" title="{{ config('app.name') }}">
        </div>
        <div class="content">
            <h2>Welcome to {{ $clinicName }}</h2>
            <p>Hello {{ $recipientName }},</p>
            <p>An administrator has created your {{ strtolower($roleLabel) }} account on {{ config('app.name') }} for <strong>{{ $clinicName }}</strong>.</p>
            <p>You can sign in with the following details:</p>
            <div class="credentials">
                <div class="credentials-row">
                    <div class="credentials-label">Email</div>
                    <div class="credentials-value">{{ $loginEmail }}</div>
                </div>
                <div class="credentials-row">
                    <div class="credentials-label">Password</div>
                    <div class="credentials-value">{{ $password }}</div>
                </div>
            </div>
            <p>For your security, please change this password after you sign in.</p>
            <p>Thank you,<br>{{ config('app.name') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
