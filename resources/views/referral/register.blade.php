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
            background: #fff;
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

        .phone-input-wrap {
            display: flex;
            align-items: stretch;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .phone-input-wrap:focus-within {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(16, 144, 20, 0.15);
        }

        .phone-prefix {
            display: flex;
            align-items: center;
            padding: 0 0.85rem;
            background: #f3f4f6;
            color: #4b5563;
            font-weight: 600;
            font-size: 0.95rem;
            border-right: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .phone-input-wrap input {
            border: 0;
            border-radius: 0;
            box-shadow: none !important;
            flex: 1;
            min-width: 0;
        }

        .field-hint {
            color: var(--muted);
            font-size: 0.78rem;
            margin-top: 0.3rem;
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
            .referral-card { padding: 1.25rem; }
            .referral-wrap { padding: 1.25rem 0.85rem 2rem; }
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

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .referral-footer {
            text-align: center;
            margin-top: 1.75rem;
        }

        .referral-footer p {
            font-size: 0.9rem;
            color: var(--muted);
            margin: 0 0 1rem;
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

        .phone-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 1000;
        }

        .phone-modal-overlay.is-open {
            display: flex;
        }

        .phone-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.18);
            overflow: hidden;
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .phone-modal-header {
            padding: 1.25rem 1.25rem 0.75rem;
            text-align: center;
        }

        .phone-modal-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 0.85rem;
            border-radius: 50%;
            background: var(--green-light);
            color: var(--green-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .phone-modal-header h3 {
            margin: 0 0 0.35rem;
            font-size: 1.2rem;
            color: var(--green-dark);
        }

        .phone-modal-header p {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .phone-modal-body {
            padding: 0.75rem 1.25rem 1.25rem;
        }

        .phone-modal-body label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }

        .phone-modal-error {
            display: none;
            margin-top: 0.65rem;
            padding: 0.65rem 0.75rem;
            border-radius: 10px;
            background: #fef2f2;
            color: #991b1b;
            font-size: 0.82rem;
            border: 1px solid #fecaca;
        }

        .phone-modal-error.is-visible {
            display: block;
        }

        .phone-modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            padding: 0 1.25rem 1.25rem;
        }

        .modal-btn {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 0;
            transition: background 0.15s, color 0.15s;
        }

        .modal-btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .modal-btn-secondary:hover {
            background: #e5e7eb;
        }

        .modal-btn-primary {
            background: var(--green);
            color: #fff;
        }

        .modal-btn-primary:hover {
            background: var(--green-dark);
        }

        @media (max-width: 480px) {
            .phone-modal-actions {
                grid-template-columns: 1fr;
            }

            .phone-modal-header h3 {
                font-size: 1.1rem;
            }
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
                    @if($errors->has('phone'))
                        {{ $errors->first('phone') }}
                    @else
                        Please correct the errors below and try again.
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('referral.register', $affiliate->code) }}" id="referralForm" novalidate>
                @csrf

                <input type="hidden" name="country" value="{{ old('country', $indiaCountryId) }}">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">
                        @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name">
                        @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <div class="phone-input-wrap">
                        <span class="phone-prefix">+91</span>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            placeholder="10-digit mobile number"
                            required
                            autocomplete="tel-national"
                        >
                    </div>
                    <div class="field-hint">This number will be used to sign in to the Noraya app.</div>
                    @error('phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="6" autocomplete="new-password">
                    </div>
                </div>

                <button type="button" class="submit-btn" id="openPhoneConfirmBtn">Register as Patient</button>
            </form>
        </div>

        <div class="referral-footer">
            <p>Already have an account? Download the Noraya patient app to sign in.</p>
            @if(!empty($googlePlayUrl))
                <a href="{{ $googlePlayUrl }}" class="google-play-link" target="_blank" rel="noopener noreferrer">
                    <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" alt="Get it on Google Play">
                </a>
            @endif
        </div>
    </div>

    <div class="phone-modal-overlay" id="phoneConfirmModal" aria-hidden="true">
        <div class="phone-modal" role="dialog" aria-modal="true" aria-labelledby="phoneConfirmTitle">
            <div class="phone-modal-header">
                <div class="phone-modal-icon">📱</div>
                <h3 id="phoneConfirmTitle">Confirm your phone number</h3>
                <p>You can edit it below before completing registration.</p>
            </div>
            <div class="phone-modal-body">
                <label for="confirmPhone">Phone Number</label>
                <div class="phone-input-wrap">
                    <span class="phone-prefix">+91</span>
                    <input
                        type="tel"
                        id="confirmPhone"
                        inputmode="numeric"
                        pattern="[0-9]{10}"
                        maxlength="10"
                        placeholder="10-digit mobile number"
                        autocomplete="tel-national"
                    >
                </div>
                <div class="phone-modal-error" id="phoneModalError"></div>
            </div>
            <div class="phone-modal-actions">
                <button type="button" class="modal-btn modal-btn-secondary" id="cancelPhoneConfirmBtn">Go Back</button>
                <button type="button" class="modal-btn modal-btn-primary" id="confirmPhoneBtn">Confirm & Register</button>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('referralForm');
        const phoneInput = document.getElementById('phone');
        const confirmPhoneInput = document.getElementById('confirmPhone');
        const modal = document.getElementById('phoneConfirmModal');
        const modalError = document.getElementById('phoneModalError');
        const openModalBtn = document.getElementById('openPhoneConfirmBtn');
        const cancelModalBtn = document.getElementById('cancelPhoneConfirmBtn');
        const confirmModalBtn = document.getElementById('confirmPhoneBtn');

        function normalizePhone(value) {
            let digits = (value || '').replace(/\D/g, '');

            if (digits.length === 12 && digits.startsWith('91')) {
                digits = digits.slice(2);
            }

            return digits.slice(0, 10);
        }

        function bindPhoneInput(input) {
            input.addEventListener('input', function () {
                input.value = normalizePhone(input.value);
            });
        }

        bindPhoneInput(phoneInput);
        bindPhoneInput(confirmPhoneInput);

        function isValidPhone(value) {
            return /^[0-9]{10}$/.test(value);
        }

        function validateFormFields() {
            if (!form.reportValidity()) {
                return false;
            }

            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                alert('Password and confirm password must match.');
                return false;
            }

            if (!isValidPhone(phoneInput.value)) {
                alert('Please enter a valid 10-digit phone number.');
                phoneInput.focus();
                return false;
            }

            return true;
        }

        function showModalError(message) {
            modalError.textContent = message;
            modalError.classList.add('is-visible');
        }

        function hideModalError() {
            modalError.textContent = '';
            modalError.classList.remove('is-visible');
        }

        function openModal() {
            hideModalError();
            confirmPhoneInput.value = phoneInput.value;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                confirmPhoneInput.focus();
                confirmPhoneInput.select();
            }, 100);
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        openModalBtn.addEventListener('click', function () {
            phoneInput.value = normalizePhone(phoneInput.value);

            if (!validateFormFields()) {
                return;
            }

            openModal();
        });

        cancelModalBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        confirmModalBtn.addEventListener('click', function () {
            const confirmedPhone = normalizePhone(confirmPhoneInput.value);
            confirmPhoneInput.value = confirmedPhone;

            if (!isValidPhone(confirmedPhone)) {
                showModalError('Please enter a valid 10-digit phone number.');
                confirmPhoneInput.focus();
                return;
            }

            hideModalError();
            phoneInput.value = confirmedPhone;
            confirmModalBtn.disabled = true;
            confirmModalBtn.textContent = 'Registering...';
            form.submit();
        });
    </script>
</body>
</html>
