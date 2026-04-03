<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\SendApproval;
use App\Models\DoctorCredit;
use App\Models\PaymentLog;
use Mail;
use Stripe\Stripe;
use Stripe\Account;

class DoctorController extends Controller
{
    public function index()
    {        
         $doctors = User::select('*')->where('chrApproval','Y')->get(); // Assuming this is how you fetch the doctor data
        $cutPercentage = \DB::table('general_settings')
        ->where('field_name', 'percentage') // Filter by the field_name
        ->value('field_value'); 

        foreach ($doctors as $doctor) {
            // Calculate total payment from the appointment table
            $totalAmount = PaymentLog::where('dr_id', $doctor->id)->sum('amount');
            $cutAmount = ($totalAmount * $cutPercentage) / 100;
            $doctorFeeAfterCut = $totalAmount - $cutAmount;
        
            // Calculate total payment (paid appointments * doctor's fee)
            // Calculate remaining payment from the payment table
            // $paidAmount =  DoctorCredit::where('dr_id', $doctor->id)->sum('paidBy');  // Sum the paid amount for this doctor
            
            // $totalPayment = $doctorFeeAfterCut;
            // // Calculate the remaining payment
            // $remainingPayment = $totalPayment - $paidAmount;
            // $totalPayment =  $totalPayment - $paidAmount;        
            // Store the calculated values in the doctor object (or send them to the view)

            $recentPayment = PaymentLog::where('dr_id', $doctor->id)
            ->orderBy('created_at', 'desc') // Assuming 'created_at' stores the payment log timestamp
            ->first();

            // Add recent payment details if available
            if ($recentPayment) {
                $doctor->recent_payment_amount = $recentPayment->amount;
                $doctor->recent_payment_date = $recentPayment->created_at;
            } else {
                $doctor->recent_payment_amount = 0;
                $doctor->recent_payment_date = null;
            }

            $doctor->total_payment = $totalAmount;
            // $doctor->remaining_payment = $remainingPayment;
        }
    
        return view('admin.doctor',compact(['doctors'])); // Create this view
    }
    
    
    public function destroy($id)
    {
        try {
            $doctor = User::findOrFail($id); // Assuming doctors are stored in the User model
    
            if ($doctor->stripe_account_id) {
                if($doctor->test_mode == 'Y'){
                    Stripe::setApiKey(env('STRIPE__test_SECRET'));
                }else{
                   Stripe::setApiKey(env('STRIPE_SECRET')); 
                }
                
                $account = Account::retrieve($doctor->stripe_account_id);

                if ($account->type === 'custom') {
                    $account->delete(); // Only delete custom accounts
                } else {
                    // Just nullify the reference in your DB
                    $doctor->stripe_account_id = null;
                }
            }
    
            $doctor->delete();
    
            return response()->json(['message' => 'Doctor and Stripe account deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting doctor: ' . $e->getMessage()], 500);
        }
    }

    public function deleteDoctor($id)
{
    $doctor = User::find($id); // or however your model is named

    if (!$doctor) {
        return response()->json(['success' => false, 'message' => 'Doctor not found']);
    }

   try {
        // Check and set Stripe key
        if (!empty($doctor->stripe_account_id)) {
            if ($doctor->test_mode == 'Y') {
                Stripe::setApiKey(env('STRIPE__test_SECRET'));
            } else {
                Stripe::setApiKey(env('STRIPE_SECRET'));
            }

            $account = Account::retrieve($doctor->stripe_account_id);

            if ($account->type === 'custom') {
                $account->delete(); // Only delete custom accounts
            } else {
                // Just nullify the reference in your DB
                $doctor->stripe_account_id = null;
            }
        }

        // Now delete doctor record
        $doctor->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}
    
    
      public function PendingList()
    {        
        $doctors = User::select('*')->where('chrApproval','N')->get(); // Assuming this is how you fetch the doctor data
        $cutPercentage = \DB::table('general_settings')
        ->where('field_name', 'percentage') // Filter by the field_name
        ->value('field_value'); 

        foreach ($doctors as $doctor) {
            // Calculate total payment from the appointment table
            $totalAmount = PaymentLog::where('dr_id', $doctor->id)->sum('amount');
            $cutAmount = ($totalAmount * $cutPercentage) / 100;
            $doctorFeeAfterCut = $totalAmount - $cutAmount;
        
            // Calculate total payment (paid appointments * doctor's fee)
            // Calculate remaining payment from the payment table
            // $paidAmount =  DoctorCredit::where('dr_id', $doctor->id)->sum('paidBy');  // Sum the paid amount for this doctor
            
            // $totalPayment = $doctorFeeAfterCut;
            // // Calculate the remaining payment
            // $remainingPayment = $totalPayment - $paidAmount;
            // $totalPayment =  $totalPayment - $paidAmount;        
            // Store the calculated values in the doctor object (or send them to the view)

            $recentPayment = PaymentLog::where('dr_id', $doctor->id)
            ->orderBy('created_at', 'desc') // Assuming 'created_at' stores the payment log timestamp
            ->first();

            // Add recent payment details if available
            if ($recentPayment) {
                $doctor->recent_payment_amount = $recentPayment->amount;
                $doctor->recent_payment_date = $recentPayment->created_at;
            } else {
                $doctor->recent_payment_amount = 0;
                $doctor->recent_payment_date = null;
            }

            $doctor->total_payment = $totalAmount;
            // $doctor->remaining_payment = $remainingPayment;
        }
    
        return view('admin.pendingapproval',compact(['doctors'])); // Create this view
    }

    public function updateData(Request $request)
    {        
        $doctors = User::findorfail($request->id);
        $doctors->chrApproval = $request->approval;
        $doctors->save();
        $this->SendEmail($doctors->email,$doctors->name,$doctors->surname);
        return redirect()->route('admin.doctor')->with('success', 'Email Sent.');

        // return response(['message' => 'Sucsess'],200);   // Create this view`
    }



    public function updatePayment(Request $request)
{
    // Validate input
    $validated = $request->validate([
        'doctor_id' => 'required',
        'payment_amount' => 'required|numeric|min:0',
    ]);

    $cutPercentage = \DB::table('general_settings')
    ->where('field_name', 'percentage') // Filter by the field_name
    ->value('field_value'); 
    // Retrieve the doctor record
    $doctor = DoctorCredit::where('dr_id',$request->doctor_id)->first();

    if (!$doctor) {
        // If no doctor is found, return an error response
        $doctorcr = new DoctorCredit();
        $doctorcr->dr_id =$request->doctor_id;
        $doctorcr->amount = 0;
        $doctorcr->paidBy = $request->payment_amount; // Add new payment amount
        $doctorcr->save();
        

        $totalAmount = DoctorCredit::where('dr_id', $doctorcr->id)->sum('amount');

        $cutAmount = ($totalAmount * $cutPercentage) / 100;
        $doctorFeeAfterCut = $totalAmount - $cutAmount;
    
        // Calculate total payment (paid appointments * doctor's fee)
        $totalPayment = $doctorFeeAfterCut;
        // Calculate remaining payment from the payment table
        $paidAmount =  DoctorCredit::where('dr_id', $doctorcr->id)->sum('paidBy');  // Sum the paid amount for this doctor
    
        // Calculate the remaining payment
        $remainingPayment = $totalPayment - $paidAmount;
        // Update the user table if necessary (optional)
        // Return the updated remaining payment
        return response()->json([
            'success' => true,
            'updated_remaining_payment' => $remainingPayment
        ]);
        
        // HTTP status code 404 (Not Found)
    }

    // Update the remaining payment

    // Save the new remaining payment to the doctor
    if ($doctor) {
        // If record exists, update the amount
        $doctor->paidBy += $request->payment_amount; // Add new payment amount
        $doctor->save();
    }


    $totalAmount = DoctorCredit::where('dr_id', $doctor->id)->sum('amount');

    $cutAmount = ($totalAmount * $cutPercentage) / 100;
    $doctorFeeAfterCut = $totalAmount - $cutAmount;

    // Calculate total payment (paid appointments * doctor's fee)
    $totalPayment = $doctorFeeAfterCut;
    // Calculate remaining payment from the payment table
    $paidAmount =  DoctorCredit::where('dr_id', $doctor->id)->sum('paidBy');  // Sum the paid amount for this doctor

    // Calculate the remaining payment
    $remainingPayment = $totalPayment - $paidAmount;
    // Update the user table if necessary (optional)
    // Return the updated remaining payment
    return response()->json([
        'success' => true,
        'updated_remaining_payment' => $remainingPayment
    ]);
}

    public function SendEmail($email,$name,$surname){
        Mail::to($email)->send(new SendApproval($email,$name,$surname));
        return response(['sucsess' => "Email Sent"]);
    }
}
