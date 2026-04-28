<html>

<head>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
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
            color: #109014;
        }

        .content p {
            line-height: 1.6;
            font-size: 14px;
        }

        .button {
            background-color: #109014;
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
            <img src="{{ asset('assets/img/noraya-logo.png') }}" alt="{{ config('app.name') }}" title="{{ config('app.name') }}">
        </div>
        <div class="content">
            <h1>Dear {{ $user->name }},</h1>
            <p>Your registration request has been sent for approval!</p>
            <p>To complete your setup, please set up your bank details to receive your fees and keep track of all your earnings through Noraya. Click the link below to proceed:</p>
            <p><a href="{{ $onboardingUrl }}">Bank Setup</a></p>
            <p>For detailed, step-by-step instructions, please refer to the attached Bank Set up Guide</p>
            <p>If you have any questions or need assistance, feel free to contact our support team at <a href="mailto:hiwellora@gmail.com">hiwellora@gmail.com</a>.</p>
            <p>Regards,</p></br>
            <p>Noraya Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>