<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentLog;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;

class PaymentLogController extends Controller
{
    public function showPaymentLedger()
    {
        $paymentLogs = PaymentLog::query()->orderByDesc('id')->get();
        $monthlySummaries = $this->buildMonthlySummaries($paymentLogs);

        return view('admin.payment-ledger', compact('paymentLogs', 'monthlySummaries'));
    }

    public function showDoctorPaymentLedger($id)
    {
        $query = PaymentLog::query()->where('dr_id', (int) $id);
        $paymentLogs = $query->orderByDesc('id')->get();
        $selectedDoctor = User::select('id', 'name', 'surname', 'email')->find($id);
        $monthlySummaries = $this->buildMonthlySummaries($paymentLogs);

        return view('admin.payment-ledger', compact('paymentLogs', 'selectedDoctor', 'monthlySummaries'));
    }

    public function showPaymentLogs(Request $request)
    {
        $query = Payment::query()
            ->leftJoin('patients', 'payments.patient_id', '=', 'patients.id')
            ->leftJoin('users as doctors', 'payments.doctor_id', '=', 'doctors.id')
            ->leftJoin('appointment', 'payments.appointment_id', '=', 'appointment.id')
            ->select([
                'payments.*',
                'patients.name as patient_name',
                'patients.lastname as patient_lastname',
                'doctors.name as doctor_name',
                'doctors.surname as doctor_surname',
                'appointment.amount as amount',
            ]);

        if ($request->filled('doctor_id')) {
            $query->where('payments.doctor_id', (int) $request->doctor_id);
        }

        $paymentLogs = $query->orderByDesc('payments.id')->get();

        return view('admin.payment', compact('paymentLogs'));
    }

    private function buildMonthlySummaries($paymentLogs)
    {
        return $paymentLogs
            ->groupBy(function ($log) {
                $date = !empty($log->transaction_time) ? Carbon::parse($log->transaction_time) : Carbon::now();
                return $date->format('Y-m');
            })
            ->map(function ($logs, $monthKey) {
                $appointmentIds = $logs->pluck('appointment_id')->filter()->unique();
                $grossAmount = (float) $logs
                    ->filter(fn ($log) => stripos((string) $log->varStatus, 'refund') === false)
                    ->sum('amount');

                $refundLogs = $logs->filter(fn ($log) => stripos((string) $log->varStatus, 'refund') !== false);
                $refundAmount = (float) $refundLogs->sum('amount');
                $finalPayout = $grossAmount - $refundAmount;
                $completedTransfers = $logs->filter(fn ($log) => strtolower((string) $log->varStatus) === 'completed')->count();

                return [
                    'month_key' => $monthKey,
                    'month_label' => Carbon::createFromFormat('Y-m', $monthKey)->format('F Y'),
                    'appointment_count' => $appointmentIds->count(),
                    'transaction_count' => $logs->count(),
                    'gross_amount' => $grossAmount,
                    'refund_count' => $refundLogs->count(),
                    'refund_amount' => $refundAmount,
                    'final_payout' => $finalPayout,
                    'completed_transfers' => $completedTransfers,
                ];
            })
            ->sortByDesc('month_key')
            ->values();
    }
}
