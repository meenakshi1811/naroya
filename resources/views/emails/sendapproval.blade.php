<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Approved</title>
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
            margin-right: 20px;
        }

        .content p {
            margin: 0 0 14px;
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
            color: #109014 !important;
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
                <h1 class="title">Registration Approved</h1>

                <p>Dear Dr. {{ trim($name . ' ' . $surname) }},</p>

                <p>Your registration with {{ config('app.name') }} has been approved.</p>

                <div class="info-card">
                    As part of your setup, you will receive an email invitation from SignatureRx to create online prescriptions for your patients. Please follow the instructions in that invitation to enable prescribing through {{ config('app.name') }}.
                </div>

                <p>To assist you, we’ve attached an <strong>Issuing Prescription Guide</strong> with step-by-step instructions for prescribing medication through the SignatureRx dashboard.</p>

                <p>If you have any questions or need support, feel free to contact us at <a class="support-link" href="mailto:contactnoraya@gmail.com">contactnoraya@gmail.com</a>.</p>

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
