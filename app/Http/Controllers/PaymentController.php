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
use App\Models\DoctorCredit;
use Stripe\Account;
use App\Models\User;
use App\Models\GeneralSetting;
use Stripe\Token;
use Stripe\Refund;
use App\Models\RefundLog;
use App\Models\BookCount;
use App\Models\Payment;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


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
        $headers     = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);

        if (empty($headerArray[1])) {
            return response()->json([
                'message' => 'Unauthorized request',
                'data'    => ['error' => 'Unauthorized request'],
            ], 401);
        }

        $tokenData = decrypt($headerArray[1]);
        $patients  = !empty($tokenData['id']) ? Patients::find($tokenData['id']) : null;

        if (empty($patients)) {
            return response()->json([
                'message' => 'Unauthorized request',
                'data'    => ['error' => 'Unauthorized request'],
            ], 401);
        }

        $request->validate([
            'doctor'            => 'required',
            'payment_method_id' => 'required', // Razorpay payment id
            'appointment_id'    => 'required',
            'amount'            => 'required|numeric|min:0.01',
        ]);

        $doctor = User::find($request->doctor);
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found.',
                'data'    => ['error' => 'Invalid doctor'],
            ], 400);
        }

        $appointment = Appointment::find($request->appointment_id);
        if (!$appointment) {
            return response()->json([
                'message' => 'Invalid appointment ID.',
                'data'    => ['error' => 'Appointment not found'],
            ], 400);
        }

        try {
            $razorpayKey    = env('RAZORPAY_KEY_ID');
            $razorpaySecret = env('RAZORPAY_KEY_SECRET');

            $razorpayResponse = Http::withBasicAuth($razorpayKey, $razorpaySecret)
                ->get('https://api.razorpay.com/v1/payments/' . $request->payment_method_id);

            if (!$razorpayResponse->ok()) {
                Payment::create([
                    'status'         => 'failed',
                    'transaction_id' => $request->payment_method_id,
                    'patient_id'     => $patients->id,
                    'doctor_id'      => $request->doctor,
                    'appointment_id' => $request->appointment_id,
                    'created_at'     => now(),
                ]);

                return response()->json([
                    'message' => 'Unable to verify payment with Razorpay.',
                    'data'    => ['error' => $razorpayResponse->json()],
                ], 400);
            }

            $paymentData = $razorpayResponse->json();
            $status = (($paymentData['status'] ?? null) === 'captured') ? 'success' : 'failed';

            Payment::create([
                'status'         => $status,
                'transaction_id' => $request->payment_method_id,
                'patient_id'     => $patients->id,
                'doctor_id'      => $request->doctor,
                'appointment_id' => $request->appointment_id,
                'created_at'     => now(),
            ]);

            Appointment::where('id', $request->appointment_id)
                ->update(['charIsPaid' => $status === 'success' ? 'Y' : 'N']);

            return response()->json([
                'message' => $status === 'success' ? 'Payment successful.' : 'Payment failed.',
                'status'  => $status,
                'payment' => $paymentData,
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay payment verification failed.', [
                'error' => $e->getMessage(),
            ]);

            Payment::create([
                'status'         => 'failed',
                'transaction_id' => $request->payment_method_id,
                'patient_id'     => $patients->id,
                'doctor_id'      => $request->doctor,
                'appointment_id' => $request->appointment_id,
                'created_at'     => now(),
            ]);

            return response()->json([
                'message' => 'Payment verification failed.',
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