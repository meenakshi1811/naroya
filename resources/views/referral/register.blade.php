<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
    <title>Patient Registration — {{ $affiliate->name }}</title>
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

        .referral-wrap {
            max-width: 520px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        .referral-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .referral-brand img {
            width: 48px;
            height: 48px;
            margin-bottom: 0.75rem;
        }

        .referral-brand h1 {
            font-size: 1.5rem;
            color: var(--green-dark);
            margin: 0 0 0.35rem;
            font-weight: 700;
        }

        .referral-brand p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .referral-affiliate-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--green-light);
            color: var(--green-dark);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            margin-top: 0.75rem;
        }

        .referral-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 8px 30px rgba(15, 127, 19, 0.08);
        }

        .referral-card h2 {
            font-size: 1.15rem;
            margin: 0 0 0.35rem;
            color: var(--green-dark);
        }

        .referral-card .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(16, 144, 20, 0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
        }

        .form-error {
            color: #dc2626;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background: var(--green-light);
            color: var(--green-dark);
            border: 1px solid #b8dfb9;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .submit-btn {
            width: 100%;
            margin-top: 0.5rem;
            padding: 0.85rem 1rem;
            background: var(--green);
            color: #fff;
            border: 0;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s;
        }

        .submit-btn:hover {
            background: var(--green-dark);
        }

        .referral-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .referral-footer a {
            color: var(--green-dark);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="referral-wrap">
        <div class="referral-brand">
            <img src="{{ asset('assets/img/fav.png') }}" alt="Noraya">
            <h1>Noraya</h1>
            <p>Register as a patient and connect with trusted doctors.</p>
            <div class="referral-affiliate-badge">
                Referred by {{ $affiliate->name }}
            </div>
        </div>

        <div class="referral-card">
            <h2>Create your account</h2>
            <p class="subtitle">Fill in your details below to get started.</p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    Please correct the errors below and try again.
                </div>
            @endif

            <form method="POST" action="{{ route('referral.register', $affiliate->code) }}">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                        @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}">
                        @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required>
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="{{ old('country') }}">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" value="{{ old('state') }}">
                    </div>
                </div>

                <button type="submit" class="submit-btn">Register as Patient</button>
            </form>
        </div>

        <div class="referral-footer">
            Already have an account? Download the Noraya patient app to sign in.<br>
            <a href="{{ url('/privacy-policy') }}">Privacy Policy</a> ·
            <a href="{{ url('/terms-and-conditions-patient') }}">Terms &amp; Conditions</a>
        </div>
    </div>
</body>
</html>
