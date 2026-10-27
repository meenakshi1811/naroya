<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your {{ $brand }} account</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f5f3; font-family:Arial, Helvetica, sans-serif; color:#374151;">
    {{-- Preview text shown in the inbox list --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        {{ $addedBy }} added you to {{ $clinicName }}. Here are your sign-in details.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f2f5f3;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e3e9e4;">

                    {{-- Brand bar --}}
                    <tr>
                        <td style="background-color:#109014; height:6px; line-height:6px; font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:28px 32px 8px;">
                            <img src="{{ asset('assets/img/noraya-logo.png') }}" alt="{{ $brand }}" width="140" style="display:block; width:140px; max-width:140px; height:auto; border:0;">
                        </td>
                    </tr>

                    {{-- Heading --}}
                    <tr>
                        <td align="center" style="padding:16px 32px 4px;">
                            <span style="display:inline-block; background-color:#e8f5e9; color:#0f7f13; font-size:12px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; padding:6px 12px; border-radius:999px;">Clinic team</span>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:14px 32px 0;">
                            <h1 style="margin:0; font-size:24px; line-height:32px; color:#111827; font-weight:bold;">Welcome to {{ $clinicName }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:10px 40px 0;">
                            <p style="margin:0; font-size:15px; line-height:24px; color:#6b7280;">
                                Hi {{ $employeeName }}, <strong style="color:#374151;">{{ $addedBy }}</strong> has added you as a staff member. Use the details below to sign in to the {{ $brand }} app.
                            </p>
                        </td>
                    </tr>

                    {{-- Credentials --}}
                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4faf4; border:1px solid #cfe8d1; border-radius:12px;">
                                <tr>
                                    <td style="padding:18px 22px 6px;">
                                        <p style="margin:0; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#6b7280;">Email</p>
                                        <p style="margin:6px 0 0; font-size:16px; line-height:22px; color:#111827; font-weight:bold; word-break:break-all;">{{ $loginEmail }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 22px;">
                                        <div style="border-top:1px solid #d9ecda; height:1px; line-height:1px; font-size:0;">&nbsp;</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 22px 18px;">
                                        <p style="margin:0; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#6b7280;">Password</p>
                                        <p style="margin:6px 0 0; font-family:'Courier New', Courier, monospace; font-size:18px; line-height:24px; color:#0f7f13; font-weight:bold; letter-spacing:0.06em; word-break:break-all;">{{ $password }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Steps --}}
                    <tr>
                        <td style="padding:20px 32px 4px;">
                            <p style="margin:0 0 12px; font-size:14px; font-weight:bold; color:#111827;">How to sign in</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                @foreach ([
                                    'Open the ' . $brand . ' doctor app on your phone.',
                                    'Sign in with the email and password above.',
                                    'You will see ' . $clinicName . '\'s patients and token queue.',
                                ] as $step)
                                    <tr>
                                        <td width="34" valign="top" style="padding:0 0 12px;">
                                            <span style="display:inline-block; width:24px; height:24px; line-height:24px; text-align:center; border-radius:50%; background-color:#109014; color:#ffffff; font-size:12px; font-weight:bold;">{{ $loop->iteration }}</span>
                                        </td>
                                        <td valign="top" style="padding:2px 0 12px; font-size:14px; line-height:21px; color:#374151;">{{ $step }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Clinic --}}
                    <tr>
                        <td style="padding:8px 32px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb; border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#9ca3af;">Your clinic</p>
                                        <p style="margin:6px 0 0; font-size:15px; font-weight:bold; color:#111827;">{{ $clinicName }}</p>
                                        @if($clinicAddress)
                                            <p style="margin:4px 0 0; font-size:13px; line-height:19px; color:#6b7280;">{{ $clinicAddress }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Security note --}}
                    <tr>
                        <td style="padding:20px 32px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff8eb; border-radius:10px;">
                                <tr>
                                    <td style="padding:12px 16px; font-size:13px; line-height:20px; color:#92400e;">
                                        Keep these details private. If you didn't expect this email, please contact {{ $clinicName }}.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="background-color:#f8faf9; border-top:1px solid #eef1ef; padding:18px 32px;">
                            <p style="margin:0; font-size:12px; line-height:18px; color:#9ca3af;">
                                &copy; {{ date('Y') }} {{ $brand }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
