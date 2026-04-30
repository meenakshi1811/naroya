<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use Stripe\PaymentMethod;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use App\Models\Patients;
use App\Models\Appointment;
use App\Models\PaymentLog;
use App\Models\DoctorCredit;
use Stripe\Account;
use App\Models\User;
use App\Models\GeneralSetting;
use Stripe\Token;
use Stripe\Refund;
use App\Models\RefundLog;
use App\Models\BookCount;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Log;


class PaymentController extends Controller
{

    /**
     * Helper to set the correct Stripe API key based on test_mode.
     */
    private function setStripeKey($testMode = 'N', $doctorTestMode = 'N')
    {
        if ($testMode === 'Y' && $doctorTestMode === 'Y') {
            Stripe::setApiKey(env('STRIPE__test_SECRET'));
        } else {
            Stripe::setApiKey(env('STRIPE_SECRET'));
        }
    }

    public function showPaymentForm()
    {
        return view('payment');
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer',
            'dr_id' => 'required|integer',
            'appointment_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:10',
            'razorpay_payment_id' => 'required|string|max:255',
            'razorpay_order_id' => 'nullable|string|max:255',
            'razorpay_signature' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'response_payload' => 'nullable|string',
        ]);

        $paymentLog = PaymentLog::create([
            'patient_id' => $validated['patient_id'],
            'dr_id' => $validated['dr_id'],
            'appointment_id' => $validated['appointment_id'],
            'payment_id' => $validated['razorpay_payment_id'],
            'varStatus' => $validated['status'] ?? 'captured',
            'amount' => $validated['amount'],
            'transaction_time' => now(),
            'response' => $validated['response_payload'] ?? json_encode([
                'gateway' => 'razorpay',
                'order_id' => $validated['razorpay_order_id'] ?? null,
                'signature' => $validated['razorpay_signature'] ?? null,
            ]),
            'description' => $validated['description'] ?? 'Dummy Razorpay frontend payment',
        ]);

        return redirect()->route('payment.success')->with('payment_log_id', $paymentLog->id);
    }

    public function success()
    {
        return view('payment', [
            'paymentStatus' => 'success',
            'paymentLogId' => session('payment_log_id'),
        ]);
    }

    public function failure()
    {
        return view('payment', [
            'paymentStatus' => 'failed',
            'paymentLogId' => null,
        ]);
    }

    public function createPaymentMethod(Request $request)
    {
        // Use test_mode from request if provided
        $testMode = $request->input('test_mode', 'N');
        $this->setStripeKey($testMode, $testMode); // both sides must agree

        try {
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number'    => '4242424242424242',
                    'exp_month' => 12,
                    'exp_year'  => 2025,
                    'cvc'       => '123',
                ],
            ]);

            return response()->json([
                'payment_method_id' => $paymentMethod->id,
                'message'           => 'Payment method created successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function ProcessPayment(Request $request)
    {
        // ── Auth ────────────────────────────────────────────────────────────
        $headers     = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);

        if (!empty($headerArray[1])) {
            $tokenData = decrypt($headerArray[1]);
            if (!empty($tokenData['id'])) {
                $patients = Patients::find($tokenData['id']);
            }
        } else {
            return response()->json([
                'message' => 'Unauthorized request',
                'data'    => ['error' => 'Unauthorized request'],
            ], 401);
        }

        try {
            if (!empty($patients)) {
                Log::info('request data', ['request' => $request->all()]);
                // ── Validation ───────────────────────────────────────────────
                $request->validate([
                    'doctor'            => 'required',
                    'currency'          => 'required',
                    'payment_method_id' => 'required',
                    'appointment_id'    => 'required',
                    'amount'            => 'required|numeric|min:0.01',
                ]);

                $doctor   = User::find($request->doctor);
                $testMode = $request->input('test_mode', 'N');

                // ── Set correct Stripe key ───────────────────────────────────
                // Both the request AND the doctor record must have test_mode = Y
                $this->setStripeKey($testMode, $doctor->test_mode ?? 'N');

                // ── Validate Doctor ──────────────────────────────────────────
                if (!$doctor || !$doctor->stripe_account_id) {
                    return response()->json([
                        'message' => 'Doctor does not have a connected Stripe account.',
                        'data'    => ['error' => 'Invalid doctor account'],
                    ], 400);
                }

                // ── Validate Appointment ─────────────────────────────────────
                $appointment = Appointment::find($request->appointment_id);
                if (!$appointment) {
                    return response()->json([
                        'message' => 'Invalid appointment ID.',
                        'data'    => ['error' => 'Appointment not found'],
                    ], 400);
                }

                $appointmentDate = $appointment->varAppointment;
                $appointmentTime = $appointment->startTime;

                // Check if another appointment already occupies this slot
                $existingAppointment = Appointment::where('dr_id', $request->doctor)
                    ->where('varAppointment', $appointmentDate)
                    ->where('startTime', $appointmentTime)
                    ->where('charIsPaid', 'Y')
                    ->where('id', '!=', $request->appointment_id)
                    ->first();

                if ($existingAppointment) {
                    return response()->json([
                        'message' => 'This time slot is already booked. Please choose a different slot.',
                        'data'    => ['error' => 'Doctor is not available at this time.'],
                    ], 400);
                }

                // ── Fee Calculations ─────────────────────────────────────────
                $adminPercentage  = GeneralSetting::where('field_name', 'percentage')->value('field_value');
                $doctorPercentage = 100 - $adminPercentage;

                // Work in pence/cents from the start to avoid rounding issues
                $currency           = strtolower($request->currency); // e.g. 'gbp' or 'eur'
                $amountInCents      = (int) round($request->amount * 100);
                $adminAmountInCents = (int) round(($adminPercentage / 100) * $amountInCents);

                // ── Book Counts ──────────────────────────────────────────────
                $patientBookCount   = BookCount::where('patient_id', $patients->id)->sum('booked');
                $patientRebookCount = BookCount::where('patient_id', $patients->id)->sum('rebooked');
                $totalBookCount     = BookCount::sum('booked');
                $totalRebookCount   = BookCount::sum('rebooked');

                // ── Step 1: Create Customer on Platform Account ──────────────
                $customer = Customer::create([
                    'name'     => $patients->name . ' ' . $patients->lastname,
                    'email'    => $patients->email,
                    'metadata' => ['user_id' => $patients->id],
                ]);

                // ── Step 2: Attach Payment Method to Platform Customer ───────
                // The PaymentMethod MUST stay on the platform account (not the
                // connected account) when using transfer_data + on_behalf_of.
                $paymentMethod = PaymentMethod::retrieve($request->payment_method_id);
                $paymentMethod->attach(['customer' => $customer->id]);

                Customer::update($customer->id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->id,
                    ],
                ]);

                // ── Step 3: Create PaymentIntent on Platform ─────────────────
                // FIX: Added `on_behalf_of` so Stripe resolves the PaymentMethod
                //      against the platform account (where it was created), not
                //      the connected account — eliminating the "No such
                //      PaymentMethod" error.
                $paymentIntent = PaymentIntent::create([
                    'amount'                 => $amountInCents,
                    'currency'               => $currency, // from request formdata (e.g. 'gbp' or 'eur')
                    'payment_method'         => $paymentMethod->id,
                    'customer'               => $customer->id,
                    'off_session'            => true,
                    'confirm'                => true,
                    // on_behalf_of makes the connected account the merchant of
                    // record for the payment (statement descriptor, card fees)
                    // while the PaymentMethod itself stays on the platform.
                    'on_behalf_of'           => $doctor->stripe_account_id,
                    'transfer_data'          => [
                        'destination' => $doctor->stripe_account_id,
                    ],
                    // FIX: was previously $adminAmount * 100, which double-
                    //      converted dollars→cents. Now computed directly in cents.
                    'application_fee_amount' => $adminAmountInCents,
                    'metadata'               => [
                        'appointment_id' => $request->appointment_id,
                        'patient_id'     => $patients->id,
                        'test_mode'      => $testMode,
                    ],
                ]);

                Log::info('PaymentIntent created successfully.', [
                    'payment_intent_id' => $paymentIntent->id,
                    'status'            => $paymentIntent->status,
                    'test_mode'         => $testMode,
                ]);

                // ── Step 4: Update Appointment to Pending ────────────────────
                Appointment::where('id', $request->appointment_id)
                    ->update(['charIsPaid' => 'P']);

                // Cancel any other appointment for the same slot
                Appointment::where('dr_id', $request->doctor)
                    ->where('varAppointment', $appointment->varAppointment)
                    ->where('startTime', $appointment->startTime)
                    ->where('id', '!=', $request->appointment_id)
                    ->update(['chrIsCanceled' => 'Y']);

                // ── Step 5: Log Payment ──────────────────────────────────────
                PaymentLog::create([
                    'patient_id'       => $patients->id,
                    'dr_id'            => $request->doctor,
                    'appointment_id'   => $request->appointment_id,
                    'amount'           => $request->amount,
                    'payment_id'       => $paymentIntent->id,
                    'varStatus'        => 'pending',
                    'transaction_time' => now(),
                    'response'         => json_encode($paymentIntent),
                    'description'      => 'Payment initiated' . ($testMode === 'Y' ? ' (test mode)' : ''),
                ]);

                return response()->json([
                    'message'        => 'Payment is in progress.',
                    'payment_intent' => $paymentIntent,
                    'test_mode'      => $testMode,
                    'book_counts'    => [
                        'patient_book_count'   => (string) $patientBookCount,
                        'patient_rebook_count' => (string) $patientRebookCount,
                        'total_book_count'     => (string) $totalBookCount,
                        'total_rebook_count'   => (string) $totalRebookCount,
                    ],
                ]);
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe API error during payment processing.', [
                'error'     => $e->getMessage(),
                'test_mode' => $request->input('test_mode', 'N'),
            ]);
            return response()->json([
                'message' => $e->getMessage(),
                'data'    => ['error' => $e->getMessage()],
            ], 400);
        }
    }

    public function updatePaymentSetupStatus(Request $request)
    {
        try {
            $userData = $request->user();
            $userId   = $userData->id;
            $testMode = $request->input('test_mode', 'N');

            $user = User::findOrFail($userId);

            if (!$user || !$user->stripe_account_id) {
                return response()->json([
                    'message'                => 'Stripe account not found for this user.',
                    'isPaymentFlowRegistered' => false,
                ], 200);
            }

            // ── Set correct Stripe key ───────────────────────────────────────
            $this->setStripeKey($testMode, $testMode);

            $account = Account::retrieve($user->stripe_account_id);

            if ($account->charges_enabled && $account->payouts_enabled) {
                $user->isPaymentFlowRegistered = true;
                $user->save();

                return response()->json([
                    'message'                => 'Payment setup completed successfully.',
                    'isPaymentFlowRegistered' => true,
                    'test_mode'              => $testMode,
                ]);
            } else {
                $user->isPaymentFlowRegistered = false;
                $user->save();

                return response()->json([
                    'message'                => 'Payment setup is incomplete or not enabled.',
                    'isPaymentFlowRegistered' => false,
                    'test_mode'              => $testMode,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving Stripe account status.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function processRefund(Request $request)
    {
        return response()->json([
            'message' => 'Refund initiated successfully.',
            'data'    => $refund,
        ], 200);
    }
}
