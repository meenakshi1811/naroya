<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
    <title>Password Reset Successful — Noraya</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --green: #109014;
            --green-dark: #0f7f13;
            --green-light: #e8f5e9;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #d7e7d8;
            --bg: #f4f7f4;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Figtree', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(160deg, #f8fbf8 0%, var(--bg) 45%, #eef5ee 100%);
            color: var(--text);
            line-height: 1.5;
        }

        .auth-wrap {
            max-width: 520px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .auth-brand-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 0.75rem;
            border-radius: 12px;
        }

        .auth-brand h1 {
            font-size: 1.5rem;
            color: var(--green-dark);
            margin: 0 0 0.35rem;
            font-weight: 700;
        }

        .auth-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem 1.75rem;
            box-shadow: 0 8px 30px rgba(15, 127, 19, 0.08);
            text-align: center;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 1.25rem;
            border-radius: 50%;
            background: linear-gradient(145deg, #d7f0d8 0%, var(--green-light) 100%);
            color: var(--green-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(16, 144, 20, 0.18);
        }

        .success-icon svg {
            width: 34px;
            height: 34px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .auth-card h2 {
            font-size: 1.35rem;
            margin: 0 0 0.75rem;
            color: var(--green-dark);
        }

        .success-message {
            margin: 0 auto 1.5rem;
            max-width: 380px;
            color: #4b5563;
            font-size: 0.98rem;
            line-height: 1.6;
        }

        .success-divider {
            height: 1px;
            background: #eef2ef;
            margin: 1.5rem 0;
        }

        .success-note {
            margin: 0 0 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .google-play-link {
            display: inline-block;
            line-height: 0;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .google-play-link:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .google-play-link img {
            height: 52px;
            width: auto;
        }

        .success-action {
            margin-top: 1.25rem;
        }

        .success-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
            padding: 0.85rem 1.25rem;
            background: var(--green);
            color: #fff;
            border: 0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            transition: background 0.15s;
        }

        .success-btn:hover {
            background: var(--green-dark);
        }

        @media (max-width: 480px) {
            .auth-wrap { padding: 1.25rem 0.85rem 2rem; }
            .auth-card { padding: 1.5rem 1.25rem; }

            .auth-brand {
                display: flex;
                align-items: flex-start;
                gap: 0.85rem;
                text-align: left;
            }

            .auth-brand-logo {
                width: 56px;
                height: 56px;
                margin-bottom: 0;
                flex-shrink: 0;
            }

            .auth-brand-text {
                flex: 1;
                min-width: 0;
            }

            .auth-brand h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrap">
        <div class="auth-brand">
            <img src="{{ asset('assets/img/patient-logo.png') }}" alt="Noraya" class="auth-brand-logo">
            <div class="auth-brand-text">
                <h1>Noraya</h1>
            </div>
        </div>

        <div class="auth-card">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 6L9 17l-5-5"></path>
                </svg>
            </div>

            <h2>Password Reset Successful</h2>

            <p class="success-message">
                @if($accountType === 'doctor')
                    Your doctor account password has been updated. Open the Noraya Doctor app and sign in with your new password.
                @else
                    Your patient account password has been updated. Open the Noraya app and sign in with your phone number and new password.
                @endif
            </p>

            @if($accountType === 'patient' && !empty($googlePlayUrl))
                <div class="success-divider"></div>
                <p class="success-note">Get the Noraya Patient App</p>
                <a href="{{ $googlePlayUrl }}" class="google-play-link" target="_blank" rel="noopener noreferrer">
                    <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" alt="Get it on Google Play">
                </a>
            @endif

            <div class="success-action">
                <a href="{{ config('app.url') }}" class="success-btn">Done</a>
            </div>
        </div>
    </div>
</body>
</html>
