<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentLog;
use App\Models\User;

class PaymentLogController extends Controller
{

    public function showPaymentLogs(Request $request)
    {
        $query = PaymentLog::query();
        $selectedDoctor = null;

        if ($request->filled('doctor_id')) {
            $doctorId = (int) $request->doctor_id;
            $query->where('dr_id', $doctorId);
            $selectedDoctor = User::select('id', 'name', 'surname', 'email')->find($doctorId);
        }

        $paymentLogs = $query->get();

        return view('admin.payment', compact('paymentLogs', 'selectedDoctor'));
    }
   
}
