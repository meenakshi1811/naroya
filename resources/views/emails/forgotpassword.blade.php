<x-mail::message>
Hello,

We received a request to change your password on {{ config('app.name') }}.

To reset your password, please click the link below:

<a href="{{ route('password.reset', ['token' => $token]) }}">Reset Password</a>

If you did not request a password reset, please ignore this email.

Thank you,
{{ config('app.name') }}
</x-mail::message>
