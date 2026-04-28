<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DoctorCredit;
use App\Models\GeneralSetting;
use App\Mail\SendApproval;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Account;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = $this->getDoctors('Y');
        return view('admin.doctor', compact('doctors'));
    }

    public function PendingList()
    {
        $doctors = $this->getDoctors('N');
        return view('admin.pendingapproval', compact('doctors'));
    }

    
    private function getDoctors($approval)
    {
        $cutPercentage = $this->getCutPercentage();

        $doctors = User::where('chrApproval', $approval)
            ->with(['categoryRel:id,title', 'countryRel:id,countryname'])
            ->withSum('paymentLogs as total_payment', 'amount')
            ->with(['paymentLogs' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->get();

        return $doctors->map(function ($doctor) use ($cutPercentage) {

            $totalAmount = $doctor->total_payment ?? 0;

            $cutAmount = ($totalAmount * $cutPercentage) / 100;

            $recentPayment = $doctor->paymentLogs->first();

            $doctor->recent_payment_amount = $recentPayment->amount ?? 0;
            $doctor->recent_payment_date = $recentPayment->created_at ?? null;

            $doctor->total_payment = $totalAmount;

            return $doctor;
        });
    }

   
    public function destroy($id)
    {
        try {
            $doctor = User::findOrFail($id);

            $this->handleStripeDeletion($doctor);

            $doctor->delete();

            return response()->json([
                'message' => 'Doctor and Stripe account deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting doctor: ' . $e->getMessage()
            ], 500);
        }
    }

    
    private function handleStripeDeletion($doctor)
    {
        if (!$doctor->stripe_account_id) {
            return;
        }

        Stripe::setApiKey(
            $doctor->test_mode == 'Y'
                ? env('STRIPE__test_SECRET')
                : env('STRIPE_SECRET')
        );

        $account = Account::retrieve($doctor->stripe_account_id);

        if ($account->type === 'custom') {
            $account->delete();
        } else {
            $doctor->stripe_account_id = null;
            $doctor->save();
        }
    }

   
    public function updateData(Request $request)
    {
        $doctor = User::findOrFail($request->id);
        $doctor->chrApproval = $request->approval;
        $doctor->save();

        $this->sendEmail($doctor);

        if ($request->ajax()) {
            return response()->json([
                'message' => 'Doctor approved successfully.',
                'redirect_url' => url('/admin/doctor'),
            ]);
        }

        return redirect('/admin/doctor')
            ->with('success', 'Doctor approved successfully.');
    }

  
    public function updatePayment(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required',
            'payment_amount' => 'required|numeric|min:0',
        ]);

        $cutPercentage = $this->getCutPercentage();

        $credit = DoctorCredit::firstOrCreate(
            ['dr_id' => $request->doctor_id],
            ['amount' => 0, 'paidBy' => 0]
        );

        // Add payment
        $credit->increment('paidBy', $request->payment_amount);

        // Calculations
        $totalAmount = DoctorCredit::where('dr_id', $request->doctor_id)->sum('amount');
        $paidAmount  = DoctorCredit::where('dr_id', $request->doctor_id)->sum('paidBy');

        $cutAmount = ($totalAmount * $cutPercentage) / 100;

        $remainingPayment = ($totalAmount - $cutAmount) - $paidAmount;

        return response()->json([
            'success' => true,
            'updated_remaining_payment' => $remainingPayment
        ]);
    }

    
    private function getCutPercentage()
    {
        return GeneralSetting::where('field_name', 'percentage')
            ->value('field_value') ?? 0;
    }

   
    private function sendEmail($doctor)
    {
        Mail::to($doctor->email)
            ->send(new SendApproval(
                $doctor->email,
                $doctor->name,
                $doctor->surname
            ));
    }
}
