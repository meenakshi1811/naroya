<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Received</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
            font-family: Arial, Helvetica, sans-serif;
            color: #2f3b4a;
        }

        .email-wrapper {
            width: 100%;
            padding: 28px 12px;
            box-sizing: border-box;
        }

        .email-container {
            max-width: 620px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e7edf2;
            box-shadow: 0 8px 26px rgba(16, 144, 20, 0.08);
        }

        .header {
            background: #f8fafc;
            text-align: center;
            padding: 28px 20px;
        }

        .header img {
            max-width: 170px;
            width: 100%;
            height: auto;
        }

        .content {
            padding: 30px;
            font-size: 15px;
            line-height: 1.7;
        }

        .title {
            margin: 0 0 18px;
            font-size: 22px;
            color: #109014;
        }

        .info-card {
            margin: 22px 0;
            padding: 16px 18px;
            background-color: #f2fbf3;
            border-left: 4px solid #109014;
            border-radius: 8px;
        }

        .closing {
            margin-top: 24px;
        }

        .footer {
            padding: 18px 24px;
            background: #f8fafc;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #edf2f7;
        }

        .support-link {
            color: #109014;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <img src="{{ asset('assets/img/noraya-logo.png') }}" alt="{{ config('app.name') }}" title="{{ config('app.name') }}">
            </div>

            <div class="content">
                <h1 class="title">Application Received</h1>

                <p>Dear Dr. {{ $user->name }}{{ !empty($user->surname) ? ' ' . $user->surname : '' }},</p>

                <p>Thank you for registering with {{ config('app.name') }}. We have successfully received your application.</p>

                <div class="info-card">
                    Our admin team is currently reviewing your profile and credentials. You will receive another email as soon as the verification is complete.
                </div>

                <p>We appreciate your patience and look forward to welcoming you to the platform.</p>

                <p class="closing">
                    Regards,<br>
                    <strong>{{ config('app.name') }} Team</strong>
                </p>
            </div>

            <div class="footer">
                Need help? Contact us at
                <a class="support-link" href="mailto:contactnoraya@gmail.com">contactnoraya@gmail.com</a><br>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>
</body>

</html>
