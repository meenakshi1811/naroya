<!DOCTYPE html>
<html lang="en">
<head>
<style>
        /* General Email Styles */
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding-bottom: 20px;
        }

        .header img {
            max-width: 150px;
        }

        .content {
            padding: 20px;
            text-align: left;
        }

        .content h2 {
            color: #28a745;
        }

        .content p {
            line-height: 1.6;
        }

        .button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name') }}" title="{{ config('app.name') }}">
        </div>

        <div class="content">
            <h2>Dear Dr. {{ $name }} {{ $surname }},</h2>

            <p>Your registration with Wellora has been approved!</p>
            <p>As part of your setup, you will receive an email invitation from SignatureRx which will allow you to create online prescriptions for your patients. Please follow the instructions in the invitation email to enable prescribing through Wellora.</p>

            <p>To assist you, we’ve attached a <strong>Issuing prescription guide</strong>, which provides step-by-step instructions for prescribing medication through SignatureRx dashboard.</p>
            <!-- <p><a href="https://signaturerx.co.uk/">https://signaturerx.co.uk/</a></p> -->
            <p>If you have any questions or need support, feel free to contact us at <a href="mailto:hiwellora@gmail.com">hiwellora@gmail.com</a>.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
