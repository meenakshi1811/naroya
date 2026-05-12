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


    public function showPaymentForm()
    {
        return view('payment');
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
            $razorpayKey    = config('services.razorpay.key');
            $razorpaySecret = config('services.razorpay.secret');

            if (empty($razorpayKey) || empty($razorpaySecret)) {
                Log::error('Razorpay credentials are missing.', [
                    'has_key'    => !empty($razorpayKey),
                    'has_secret' => !empty($razorpaySecret),
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
                    'message' => 'Payment gateway is not configured. Please contact support.',
                    'data'    => ['error' => 'Missing Razorpay configuration'],
                ], 500);
            }

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

  

    public function processRefund(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer',
        ]);

        $payment = Payment::find($request->payment_id);
        if (!$payment) {
            return response()->json([
                'message' => 'Payment record not found.',
            ], 404);
        }

        if ($payment->status === 'refunded') {
            return response()->json([
                'message' => 'Payment is already refunded.',
            ], 400);
        }

        $razorpayKey = config('services.razorpay.key');
        $razorpaySecret = config('services.razorpay.secret');

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            return response()->json([
                'message' => 'Refund gateway is not configured.',
            ], 500);
        }

        $paymentTransactionId = trim((string) $payment->transaction_id);

        if ($paymentTransactionId === '') {
            return response()->json([
                'message' => 'Payment transaction id is missing for this record.',
            ], 422);
        }

        try {
            $paymentDetailsResponse = Http::withBasicAuth($razorpayKey, $razorpaySecret)
                ->get('https://api.razorpay.com/v1/payments/' . $paymentTransactionId);
          
            if (!$paymentDetailsResponse->ok()) {
                return response()->json([
                    'message' => 'Unable to verify payment details from Razorpay before refund.',
                    'error' => $paymentDetailsResponse->json(),
                    'payment_transaction_id' => $paymentTransactionId,
                ], 400);
            }

            $paymentDetails = $paymentDetailsResponse->json();
            $paymentStatus = $paymentDetails['status'] ?? null;
            
            if ($paymentStatus !== 'captured') {
                return response()->json([
                    'message' => 'Only captured payments can be refunded.',
                    'payment_transaction_id' => $paymentTransactionId,
                    'razorpay_payment_status' => $paymentStatus,
                ], 422);
            }

           $refundResponse = Http::withBasicAuth($razorpayKey, $razorpaySecret)
            ->asJson()
            ->post("https://api.razorpay.com/v1/payments/{$paymentTransactionId}/refund", [
                'amount' => 50000,
            ]);

            dd($refundResponse->status(), $refundResponse->json());


            // echo'<pre>';print_r($refundResponse);exit();

            if (!$refundResponse->ok()) {
                return response()->json([
                    'message' => 'Unable to process refund from Razorpay.',
                    'error' => $refundResponse->json(),
                    'payment_transaction_id' => $paymentTransactionId,
                    'razorpay_payment_status' => $paymentStatus,
                ], 400);
            }

            $payment->status = 'refunded';
            $payment->save();

            return response()->json([
                'message' => 'Refund successful.',
                'data' => $refundResponse->json(),
            ], 200);
        } catch (\Exception $e) {
            echo'<pre>';print_r($e->getMessage());exit();

            Log::error('Razorpay refund failed.', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Refund request failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
