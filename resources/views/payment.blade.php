<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dummy Razorpay Payment</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; }
        .container { max-width: 700px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 6px 16px rgba(0,0,0,.08); }
        h1 { margin-top: 0; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; color: #444; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ccd3e0; border-radius: 6px; box-sizing: border-box; }
        textarea { min-height: 90px; }
        .full { grid-column: 1 / -1; }
        .btn { background: #0d6efd; color: #fff; border: 0; border-radius: 6px; padding: 10px 18px; cursor: pointer; }
        .msg { padding: 10px 12px; border-radius: 6px; margin-bottom: 16px; }
        .ok { background: #e9f8ef; color: #146c43; }
        .err { background: #fdeaea; color: #842029; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dummy Razorpay Payment Page</h1>

        @if(isset($paymentStatus) && $paymentStatus === 'success')
            <div class="msg ok">
                Payment data saved successfully.
                @if(!empty($paymentLogId))
                    Payment Log ID: <strong>{{ $paymentLogId }}</strong>
                @endif
            </div>
        @elseif(isset($paymentStatus) && $paymentStatus === 'failed')
            <div class="msg err">Payment failed.</div>
        @endif

        <form action="{{ route('payment.store') }}" method="POST" id="dummy-payment-form">
            @csrf
            <div class="grid">
                <div>
                    <label for="patient_id">Patient ID</label>
                    <input type="number" id="patient_id" name="patient_id" required value="{{ old('patient_id', 1) }}">
                </div>
                <div>
                    <label for="dr_id">Doctor ID</label>
                    <input type="number" id="dr_id" name="dr_id" required value="{{ old('dr_id', 1) }}">
                </div>
                <div>
                    <label for="appointment_id">Appointment ID</label>
                    <input type="number" id="appointment_id" name="appointment_id" required value="{{ old('appointment_id', 1) }}">
                </div>
                <div>
                    <label for="amount">Amount</label>
                    <input type="number" step="0.01" id="amount" name="amount" required value="{{ old('amount', 500) }}">
                </div>
                <div>
                    <label for="currency">Currency</label>
                    <input type="text" id="currency" name="currency" required value="{{ old('currency', 'INR') }}">
                </div>
                <div>
                    <label for="status">Status</label>
                    <input type="text" id="status" name="status" value="{{ old('status', 'captured') }}">
                </div>

                <input type="hidden" id="razorpay_payment_id" name="razorpay_payment_id">
                <input type="hidden" id="razorpay_order_id" name="razorpay_order_id">
                <input type="hidden" id="razorpay_signature" name="razorpay_signature">
                <input type="hidden" id="response_payload" name="response_payload">

                <div class="full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description">{{ old('description', 'Dummy frontend Razorpay payment flow') }}</textarea>
                </div>
            </div>

            <button type="button" class="btn" id="pay-now">Pay with Razorpay (Dummy)</button>
        </form>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.getElementById('pay-now').addEventListener('click', function () {
            const amount = parseFloat(document.getElementById('amount').value || '0');
            const amountInPaise = Math.round(amount * 100);

            const options = {
                key: 'rzp_test_dummy123456',
                amount: amountInPaise,
                currency: document.getElementById('currency').value || 'INR',
                name: 'Naroya Demo',
                description: 'Dummy appointment payment',
                handler: function (response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id || 'dummy_payment_id';
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id || 'dummy_order_id';
                    document.getElementById('razorpay_signature').value = response.razorpay_signature || 'dummy_signature';
                    document.getElementById('response_payload').value = JSON.stringify(response);
                    document.getElementById('dummy-payment-form').submit();
                },
                modal: {
                    ondismiss: function () {
                        window.location.href = "{{ route('payment.failure') }}";
                    }
                },
                prefill: {
                    name: 'Demo Patient',
                    email: 'patient@example.com',
                    contact: '9999999999'
                },
                theme: {
                    color: '#0d6efd'
                }
            };

            const razorpay = new Razorpay(options);
            razorpay.open();
        });
    </script>
</body>
</html>
