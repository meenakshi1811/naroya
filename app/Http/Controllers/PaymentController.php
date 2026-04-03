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
                'amount' => $amount, // Total amount in cents
                'currency' => 'GBP',
                'payment_method' => $paymentMethod->id,
                'customer' => $customer->id,
                'off_session' => true,
                'confirm' => true,
                'transfer_data' => [
                    'destination' => $doctor->stripe_account_id, // Doctor's Stripe account ID
                ],
                'application_fee_amount' => $adminAmount * 100, // Admin commission in cents
            ]);

            if ($paymentIntent->status === 'succeeded') {
                

                // Transfer to doctor's Stripe account
                // Transfer::create([
                //     'amount' => $doctorAmount * 100, // Convert to cents
                //     'currency' => $currency,
                //     'destination' => $doctor->stripe_account_id,
                // ]);

                // Log Payment
                // PaymentLog::create([
                //     'patient_id' => $patients->id,
                //     'dr_id' => $request->doctor,
                //     'appointment_id' => $request->appointment_id,
                //     'amount' => $request->amount,
                //     'payment_id' => $paymentIntent->id,
                //     'varStatus' => $paymentIntent->status,
                //     'transaction_time' => now(),
                //     'response' => json_encode($paymentIntent),
                // ]);
                
                // Log Payment to the Doctor
                PaymentLog::create([
                    'patient_id' => $patients->id,
                    'dr_id' => $request->doctor,
                    'appointment_id' => $request->appointment_id,
                    'amount' => $doctorAmount, // Doctor's share
                    'payment_id' => $paymentIntent->id,
                    'varStatus' => $paymentIntent->status,
                    'transaction_time' => now(),
                    'response' => json_encode($paymentIntent),
                    'description' => 'Payment to doctor account', // Optional description field
                ]);
            
                // Log Payment to the Admin
                PaymentLog::create([
                    'patient_id' => $patients->id,
                    'dr_id' => null, // Admin doesn’t have a doctor ID
                    'appointment_id' => $request->appointment_id,
                    'amount' => $adminAmount, // Admin's share
                    'payment_id' => $paymentIntent->id,
                    'varStatus' => $paymentIntent->status,
                    'transaction_time' => now(),
                    'response' => json_encode($paymentIntent),
                    'description' => 'Commission earned by admin', // Optional description field
                ]);


                // Update Appointment status
                $appointment = Appointment::find($request->appointment_id);
                $appointment->charIsPaid = 'Y';
                $appointment->save();
                
                
              // **Cancel other appointments at the same time for the doctor**
                Appointment::where('dr_id', $request->doctor)
                            ->where('varAppointment', $appointment->varAppointment)
                            ->where('startTime', $appointment->startTime)
                            ->where('id', '!=', $request->appointment_id) // Exclude current appointment
                            ->update(['chrIsCanceled' => 'Y']);

                // Update Doctor Credits
                $doctorCredit = DoctorCredit::where('dr_id', $request->doctor)->first();
                if ($doctorCredit) {
                    $doctorCredit->amount += $doctorAmount;
                    $doctorCredit->save();
                } else {
                    DoctorCredit::create([
                        'dr_id' => $request->doctor,
                        'amount' => $doctorAmount
                    ]);
                }
                if(isset($doctor->fcm_token) && !empty($doctor->fcm_token)){
                    $notificationController = new NotificationController();
                    $notificationController->sendPushNotification(
                    $doctor->fcm_token,
                    'Payment Received',
                    'You have received a payment of ' . $doctorAmount . ' ' . 'GBP' . ' for an appointment.',
                    'doctor'
                    );
                }
                $hasBookedBefore = BookCount::where('patient_id', $patients->id)->exists();
                  BookCount::create([
                    'patient_id' => $patients->id,
                    'dr_id' => $request->doctor,
                    'varAppointment' => $appointment->varAppointment, // appointment date
                    'booked' => $hasBookedBefore ? 0 : 1,
                    'rebooked' => $hasBookedBefore ? 1 : 0,
                ]);
                $patientBookCount = BookCount::where('patient_id', $patients->id)->sum('booked');
                $patientRebookCount = BookCount::where('patient_id', $patients->id)->sum('rebooked');

                // Global totals
                $totalBookCount = BookCount::sum('booked');
                $totalRebookCount = BookCount::sum('rebooked');
                return response()->json([
                    'message' => 'Payment successful',
                    'payment_intent' => $paymentIntent,
                    'book_counts' => [
                        'patient_book_count' => (string) $patientBookCount,
                        'patient_rebook_count' => (string) $patientRebookCount,
                        'total_book_count' => (string) $totalBookCount,
                        'total_rebook_count' => (string) $totalRebookCount,
                    ]
                ]);
            } else {
                if(isset($patients->fcm_token) && !empty($patients->fcm_token)){
                    $notificationController = new NotificationController();
                    $notificationController->sendPushNotification(
                        $patients->fcm_token,
                        'Payment Failed',
                        'Your payment for the appointment could not be processed. Please try again.',
                        'patient'
                    );
                }
                $errorMessage = $paymentIntent->last_payment_error->message ?? 'Payment failed';
                return response()->json([
                    'message' => 'Payment failed',
                    'data' => [
                        'error' => $errorMessage
                    ]
                ], 400);
            }
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
    // public function processPayment(Request $request)
    // {


    //     $headers = $request->header('Authorization');
    //     $headerArray = explode('Bearer ', $headers);
    //     if (!empty($headerArray[1])) {

    //         $tokenData = decrypt($headerArray[1]);
    //         if (!empty($tokenData['id'])) {
    //             $patients = Patients::find($tokenData['id']);
    //             if (!empty($patients)) {

    //                 // Get the payment method ID, amount, and currency from the request
    //                 $paymentMethodId = $request->input('payment_method_id');
    //                 $amount = $request->input('amount');
    //                 $currency = $request->input('currency', 'usd'); // Default to USD if not provided
    //                 // Set your Stripe Secret Key
    //                 Stripe::setApiKey(env('STRIPE_SECRET'));
    //                 try {
    //                     // Step 1: Create a PaymentIntent with the payment method ID
    //                     $paymentIntent = PaymentIntent::create([
    //                         'amount' => $amount,  // The amount in cents (e.g., $10.00 = 1000 cents)
    //                         'currency' => $currency,
    //                         'payment_method' => $paymentMethodId,
    //                         'confirmation_method' => 'manual',  // You can set 'automatic' or 'manual'
    //                         'confirm' => true  // Try to confirm the payment immediately
    //                     ]);

    //                     // Step 2: Handle the PaymentIntent status
    //                     if ($paymentIntent->status == 'succeeded') {
    //                         // Payment was successful
    //                         return response()->json(['status' => 'success', 'message' => 'Payment successful']);
    //                     } elseif ($paymentIntent->status == 'requires_action' || $paymentIntent->status == 'requires_source_action') {
    //                         // Payment requires additional action, e.g., 3D Secure
    //                         return response()->json([
    //                             'status' => 'requires_action',
    //                             'client_secret' => $paymentIntent->client_secret
    //                         ]);
    //                     } else {
    //                         // Payment failed
    //                         return response()->json(['status' => 'failed', 'message' => 'Payment failed']);
    //                     }
    //                 } catch (ApiErrorException $e) {
    //                     // Handle any Stripe API errors
    //                     return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    //                 }
    //             }
    //             return response()->json([
    //                 'message' => 'Unauthorized request',
    //                 'data' => [
    //                     'error' => 'Unauthorized request'
    //                 ]
    //             ], 401);
    //         }
    //         return response()->json([
    //             'message' => 'Unauthorized request',
    //             'data' => [
    //                 'error' => 'Unauthorized request'
    //             ]
    //         ], 401);
    //     }
    //     return response()->json([
    //         'message' => 'Unauthorized request',
    //         'data' => [
    //             'error' => 'Unauthorized request'
    //         ]
    //     ], 401);
    // }
    
       public function processRefund(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|string',
                'amount' => 'nullable|numeric',
            ]);
    
            $paymentLog = PaymentLog::where('payment_id', $request->payment_id)
                ->whereNotNull('dr_id')
                ->first();
    
            if (!$paymentLog) {
                return response()->json([
                    'message' => 'Payment record not found.',
                    'data' => ['error' => 'Invalid payment ID'],
                ], 404);
            }
    
            $doctor = User::find($paymentLog->dr_id);
            if (!$doctor || !$doctor->stripe_account_id) {
                return response()->json([
                    'message' => 'Doctor account not found.',
                    'data' => ['error' => 'Invalid doctor account'],
                ], 400);
            }
    
            $amountToRefund = $request->amount ? $request->amount * 100 : $paymentLog->amount * 100;
    
            if($doctor->test_mode == 'Y'){
                    Stripe::setApiKey(env('STRIPE__test_SECRET'));
                }else{
                   Stripe::setApiKey(env('STRIPE_SECRET')); 
                }
            // Stripe::setApiKey(env('STRIPE_SECRET'));
    
           $paymentIntent = PaymentIntent::retrieve($paymentLog->payment_id);
            if (!empty($paymentIntent->transfer_data['destination'])) {
                $connectedAccountId = $paymentIntent->transfer_data['destination'];
            } else {
                $connectedAccountId = null; // Platform account
            }
           
                // $chargeId = $paymentIntent->charges->data[0]->id;
    
           if ($connectedAccountId) {
                // Refund within connected account context
                $refund = Refund::create([
                    'payment_intent' => $paymentLog->payment_id,
                    'amount' => $amountToRefund,
                ]);
            } 
            else {
                // Refund directly under platform account
                $refund = Refund::create([
                    'payment_intent' => $paymentLog->payment_id,
                    'amount' => $amountToRefund,
                ]);
            }
    
            PaymentLog::create([
                'patient_id' => $paymentLog->patient_id,
                'dr_id' => $paymentLog->dr_id,
                'appointment_id' => $paymentLog->appointment_id,
                'amount' => -($amountToRefund / 100),
                'payment_id' => $refund->id,
                'varStatus' => 'refunded',
                'transaction_time' => now(),
                'response' => json_encode($refund),
                'description' => 'Refund processed from doctor account',
            ]);
             $patient = Patients::find($paymentLog->patient_id);
     // **Send push notification to patient**
            if (!empty($patient->fcm_token)) {

                $notificationController = new NotificationController();
                $notificationController->sendPushNotification(
                    $patient->fcm_token,
                    'Payment Failed',
                    'Your payment for the appointment could not be processed. Please try again.',
                    'patient'
                );
               
            }
    
            return response()->json([
                'message' => 'Refund successful',
                'data' => $refund,
            ], 200);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'message' => 'Refund failed',
                'data' => ['error' => $e->getMessage()],
            ], 400);
        }
    }
}
