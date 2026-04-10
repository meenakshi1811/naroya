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


class PaymentController extends Controller
{


    public function showPaymentForm()
    {
        return view('payment');  // This should point to the Blade view you created earlier
    }

    public function createPaymentMethod(Request $request)
    {
        // Set Stripe API key
        Stripe::setApiKey(env('STRIPE_SECRET'));
        // Replace with your secret Stripe key

        try {
            // Create a Payment Method (card) using the test card details
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number' => '4242424242424242',  // Visa test card number
                    'exp_month' => 12,               // Expiry month (12 = December)
                    'exp_year' => 2025,              // Expiry year (2025)
                    'cvc' => '123',                  // CVC code
                ],
            ]);

            // You can return the Payment Method ID as part of the response
            return response()->json([
                'payment_method_id' => $paymentMethod->id,
                'message' => 'Payment method created successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function ProcessPayment(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            $tokenData = decrypt($headerArray[1]);
            if (!empty($tokenData['id'])) {
                $patients = Patients::find($tokenData['id']);
            }
        } else {
            return response()->json([
                'message' => 'Unauthorized request',
                'data' => [
                    'error' => 'Unauthorized request'
                ]
            ], 401);
        }

        

        try {
            if (!empty($patients)) {
                $request->validate([
                    'doctor' => 'required',
                    'currency' => 'required',
                    'payment_method_id' => 'required',
                    'appointment_id' => 'required',
                    'amount' => 'required'
                ]);
                
                $doctor = User::find($request->doctor);
                if($request->test_mode == 'Y' && $doctor->test_mode == 'Y'){
                Stripe::setApiKey(env('STRIPE__test_SECRET'));
                }else{
                    Stripe::setApiKey(env('STRIPE_SECRET'));
                }
            // **Step 1: Retrieve Appointment Details**
                $appointment = Appointment::find($request->appointment_id);
                if (!$appointment) {
                    return response()->json([
                        'message' => 'Invalid appointment ID.',
                        'data' => ['error' => 'Appointment not found']
                    ], 400);
                }
    
                $appointmentDate = $appointment->varAppointment;
                $appointmentTime = $appointment->startTime;
    
                // **Step 2: Check if another appointment exists at the same time**
                $existingAppointment = Appointment::where('dr_id', $request->doctor)
                    ->where('varAppointment', $appointmentDate)
                    ->where('startTime', $appointmentTime)
                    ->where('charIsPaid', 'Y') // Check if already paid
                    ->where('id', '!=', $request->appointment_id) // Exclude current appointment
                    ->first();
    
                if ($existingAppointment) {
                    return response()->json([
                        'message' => 'This time slot is already booked. Please choose a different slot.',
                        'data' => ['error' => 'Doctor is not available at this time.']
                    ], 400);
                }

                // Fetch admin percentage from general settings
                $adminPercentage = GeneralSetting::where('field_name', 'percentage')->value('field_value');
                $doctorPercentage = 100 - $adminPercentage;

                if (!$doctor || !$doctor->stripe_account_id) {
                    return response()->json([
                        'message' => 'Doctor does not have a connected Stripe account.',
                        'data' => [
                            'error' => 'Invalid doctor account'
                        ]
                    ], 400);
                }

                // Step 1: Create a customer
                $customer = Customer::create([
                    'name' => $patients->name . ' ' . $patients->lastname,
                    'email' => $patients->email,
                    'metadata' => [
                        'user_id' => $patients->id
                    ],
                ]);

                // Step 2: Attach Payment Method
                $paymentMethodId = $request->payment_method_id;
                $amount = $request->amount * 100; // Convert to cents
                $currency = $request->currency;

                $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
                $paymentMethod->attach(['customer' => $customer->id]);

                Customer::update($customer->id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->id,
                    ],
                ]);
                $doctorAmount = ($doctorPercentage / 100) * $request->amount;
                $adminAmount = ($adminPercentage / 100) * $request->amount;
                // Step 3: Create PaymentIntent
            

                
                
                $paymentIntent = PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => 'GBP',
                    'payment_method' => $paymentMethod->id,
                    'customer' => $customer->id,
                    'off_session' => true,
                    'confirm' => true,
                    'metadata' => [
                        'appointment_id' => (string) $request->appointment_id,
                        'patient_id'     => (string) $patients->id,
                        'doctor_id'      => (string) $request->doctor,
                    ],
                    'transfer_data' => [
                        'destination' => $doctor->stripe_account_id,
                    ],
                    'application_fee_amount' => $adminAmount * 100,
                ]);

                return response()->json([
                    'message' => 'Payment initiated. Booking will be confirmed shortly.',
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                ]);

                
                
            }
        } catch (ApiErrorException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 400);
        }
    }

    public function updatePaymentSetupStatus(Request $request)
    {
        try {
            // Retrieve the user's Stripe account ID from your database
            $userData = $request->user();
            $userId = $userData->id;
            $testMode = $request->input('test_mode', 'N');
            // Retrieve user from the database
            $user = User::findOrFail($userId);
            if (!$user || !$user->stripe_account_id) {
                return response()->json([
                    'message' => 'Stripe account not found for this user.',
                    'isPaymentFlowRegistered' => false,
                ], 200);
            }
        if ($testMode == 'Y') {
            Stripe::setApiKey(env('STRIPE__test_SECRET'));
            } else {
                Stripe::setApiKey(env('STRIPE_SECRET'));
            }
            // Retrieve the connected account details using the Stripe API
            $account = Account::retrieve($user->stripe_account_id);
    
            // Check if the bank account is set up and payments are enabled
            if ($account->charges_enabled && $account->payouts_enabled) {
                // Update the user's payment setup status
                $user->isPaymentFlowRegistered = true;
                $user->save();
    
                return response()->json([
                    'message' => 'Payment setup completed successfully.',
                    'isPaymentFlowRegistered' => true,
                ]);
            } else {
                // If not fully set up, ensure the status remains false
                $user->isPaymentFlowRegistered = false;
                $user->save();
    
                return response()->json([
                    'message' => 'Payment setup is incomplete or not enabled.',
                    'isPaymentFlowRegistered' => false,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving Stripe account status.',
                'error' => $e->getMessage(),
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
