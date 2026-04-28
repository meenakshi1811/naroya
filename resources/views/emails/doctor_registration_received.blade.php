<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
    <meta charset="UTF-8">
    <title>Application Received</title>
</head>
<body>
    <p>Dear Dr. {{ $user->name }}{{ !empty($user->surname) ? ' ' . $user->surname : '' }},</p>

    <p>Thank you for registering with Noraya.</p>

    <p>Your application has been received and is currently under review. Once the admin team confirms your application, you will be able to access and use the app.</p>

    <p>We appreciate your patience and will notify you as soon as the review is complete.</p>

    <p>Best regards,<br>Noraya Team</p>
</body>
</html>
