<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentLog;

class PaymentLogController extends Controller
{

    public function showPaymentLogs()
    {
        // Fetch all payment logs (you can paginate this if needed)
        $paymentLogs = PaymentLog::get(); // 10 records per page

        // Pass the payment logs to the view
        return view('admin.payment', compact('paymentLogs'));
    }
   
}
