<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
    <title>Reset Password — Noraya</title>
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

        .auth-brand p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .auth-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 8px 30px rgba(15, 127, 19, 0.08);
        }

        .auth-card h2 {
            font-size: 1.15rem;
            margin: 0 0 0.35rem;
            color: var(--green-dark);
        }

        .auth-card .subtitle {
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
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(16, 144, 20, 0.15);
        }

        .form-group input[readonly] {
            background: #f9fafb;
            color: #6b7280;
        }

        .password-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrap input {
            padding-right: 2.75rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.55rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0;
            z-index: 2;
            flex-shrink: 0;
        }

        .password-toggle:hover {
            color: var(--green-dark);
            background: #f3f4f6;
        }

        .password-toggle svg {
            width: 1.15rem;
            height: 1.15rem;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .password-toggle .icon-eye-off {
            display: none;
        }

        .password-toggle.is-visible .icon-eye {
            display: none;
        }

        .password-toggle.is-visible .icon-eye-off {
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .form-error {
            color: #dc2626;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .form-alert {
            display: none;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .form-alert.is-visible {
            display: block;
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

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .auth-card { padding: 1.25rem; }
            .auth-wrap { padding: 1.25rem 0.85rem 2rem; }

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
                <p>Create a new password for your account.</p>
            </div>
        </div>

        <div class="auth-card">
            <h2>Reset your password</h2>
            <p class="subtitle">Enter a strong new password below to secure your account.</p>

            <div class="form-alert" id="formAlert"></div>

            <form id="resetPasswordForm" novalidate>
                @csrf
                <input type="hidden" name="isDoctor" value="{{ $tokenData['chrIsDr'] }}">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        required
                        readonly
                        value="{{ $tokenData['email'] ?? old('email') }}"
                        autocomplete="email"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">New Password *</label>
                        <div class="password-input-wrap">
                            <input type="text" id="password" name="password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle is-visible" data-target="password" aria-label="Hide password" aria-pressed="true" title="Hide password">
                                <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"></path>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"></path>
                                    <path d="M1 1l22 22"></path>
                                    <path d="M14.12 14.12a3 3 0 0 1-4.24-4.24"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="form-error" id="passwordClientError" style="display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password *</label>
                        <div class="password-input-wrap">
                            <input type="text" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
                            <button type="button" class="password-toggle is-visible" data-target="password_confirmation" aria-label="Hide password" aria-pressed="true" title="Hide password">
                                <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-8-10-8a18.45 18.45 0 0 1 5.06-5.94"></path>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 8 10 8a18.5 18.5 0 0 1-2.16 3.19"></path>
                                    <path d="M1 1l22 22"></path>
                                    <path d="M14.12 14.12a3 3 0 0 1-4.24-4.24"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="form-error" id="passwordConfirmClientError" style="display: none;"></div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('resetPasswordForm');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const passwordClientError = document.getElementById('passwordClientError');
        const passwordConfirmClientError = document.getElementById('passwordConfirmClientError');
        const formAlert = document.getElementById('formAlert');
        const submitBtn = document.getElementById('submitBtn');
        const csrfToken = document.querySelector('input[name="_token"]').value;
        const checkPasswordUrl = @json(route('password.check-password'));
        const updatePasswordUrl = @json(route('password.update'));

        function showClientError(element, message) {
            element.textContent = message;
            element.style.display = message ? 'block' : 'none';
        }

        function showFormAlert(message) {
            formAlert.textContent = message;
            formAlert.classList.toggle('is-visible', !!message);
        }

        async function postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(function () {
                return { message: 'Something went wrong. Please try again.' };
            });

            return { ok: response.ok, data: data };
        }

        async function validatePasswordAjax(password) {
            const result = await postJson(checkPasswordUrl, { password: password || '' });

            if (!result.ok) {
                return {
                    valid: false,
                    message: result.data.message || 'Password does not meet the required format.',
                };
            }

            return { valid: true, message: '' };
        }

        document.querySelectorAll('.password-toggle').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const input = document.getElementById(button.dataset.target);
                if (!input) {
                    return;
                }

                const willShowPassword = input.type === 'password';
                input.type = willShowPassword ? 'text' : 'password';
                button.classList.toggle('is-visible', willShowPassword);
                button.setAttribute('aria-label', willShowPassword ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', willShowPassword ? 'true' : 'false');
                button.setAttribute('title', willShowPassword ? 'Hide password' : 'Show password');
            });
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            showFormAlert('');
            showClientError(passwordClientError, '');
            showClientError(passwordConfirmClientError, '');

            const password = passwordInput.value;
            const passwordConfirmation = passwordConfirmationInput.value;

            if (!password || !passwordConfirmation) {
                showFormAlert('Please fill in both password fields.');
                return;
            }

            if (password !== passwordConfirmation) {
                showClientError(passwordConfirmClientError, 'Password and confirm password must match.');
                passwordConfirmationInput.focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Validating...';

            try {
                const passwordResult = await validatePasswordAjax(password);
                if (!passwordResult.valid) {
                    showClientError(passwordClientError, passwordResult.message);
                    passwordInput.focus();
                    return;
                }

                submitBtn.textContent = 'Resetting...';

                const payload = {
                    email: document.getElementById('email').value,
                    isDoctor: document.querySelector('input[name="isDoctor"]').value,
                    password: password,
                    password_confirmation: passwordConfirmation,
                };

                const result = await postJson(updatePasswordUrl, payload);

                if (!result.ok || !result.data.success) {
                    showFormAlert(result.data.message || 'Unable to reset password. Please try again.');
                    return;
                }

                window.location.href = result.data.redirect;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
            }
        });
    </script>
</body>
</html>
