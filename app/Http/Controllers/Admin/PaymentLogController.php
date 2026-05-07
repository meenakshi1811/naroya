<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\Payment;

class PaymentLogController extends Controller
{
    public function showPaymentLedger()
    {
        return view('admin.payment');
    }

    public function showDoctorPaymentLedger($id)
    {
        $query = PaymentLog::query()->where('dr_id', (int) $id);
        $paymentLogs = $query->get();
        $selectedDoctor = User::select('id', 'name', 'surname', 'email')->find($id);

        return view('admin.payment', compact('paymentLogs', 'selectedDoctor'));
    }

    public function showPaymentLogs(Request $request)
    {
        $query = Payment::query()
            ->leftJoin('patients', 'payments.patient_id', '=', 'patients.id')
            ->leftJoin('users as doctors', 'payments.doctor_id', '=', 'doctors.id')
            ->select([
                'payments.*',
                'patients.name as patient_name',
                'patients.lastname as patient_lastname',
                'doctors.name as doctor_name',
                'doctors.surname as doctor_surname',
                'doctors.varFees as amount',
            ]);

        if ($request->filled('doctor_id')) {
            $query->where('payments.doctor_id', (int) $request->doctor_id);
        }

        $paymentLogs = $query->orderByDesc('payments.id')->get();

        return view('admin.payment', compact('paymentLogs'));
    }
   
}
