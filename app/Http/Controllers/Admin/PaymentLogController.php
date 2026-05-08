<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\GeneralSetting;
use Carbon\Carbon;

class PaymentLogController extends Controller
{
    public function showPaymentLedger()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $paymentLogs = PaymentLog::query()
            ->whereBetween('transaction_time', [$startOfMonth, $endOfMonth])
            ->orderByDesc('id')
            ->get();
        $commissionPercentage = $this->getCommissionPercentage();
        $monthlySummaries = $this->buildMonthlySummaries($paymentLogs, $commissionPercentage);

        return view('admin.payment-ledger', compact('paymentLogs', 'monthlySummaries', 'commissionPercentage'));
    }

    public function showDoctorPaymentLedger($id)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $query = PaymentLog::query()->where('dr_id', (int) $id);
        $paymentLogs = $query
            ->whereBetween('transaction_time', [$startOfMonth, $endOfMonth])
            ->orderByDesc('id')
            ->get();
        $selectedDoctor = User::select('id', 'name', 'surname', 'email')->find($id);
        $commissionPercentage = $this->getCommissionPercentage();
        $monthlySummaries = $this->buildMonthlySummaries($paymentLogs, $commissionPercentage);

        return view('admin.payment-ledger', compact('paymentLogs', 'selectedDoctor', 'monthlySummaries', 'commissionPercentage'));
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

    public function markMonthlyPayoutAsPaid(Request $request)
    {
        $request->validate([
            'month_key' => 'required|date_format:Y-m',
            'doctor_id' => 'nullable|integer',
        ]);

        $monthDate = Carbon::createFromFormat('Y-m', $request->month_key);
        $startOfMonth = $monthDate->copy()->startOfMonth();
        $endOfMonth = $monthDate->copy()->endOfMonth();

        $query = PaymentLog::query()
            ->whereBetween('transaction_time', [$startOfMonth, $endOfMonth]);

        if ($request->filled('doctor_id')) {
            $query->where('dr_id', (int) $request->doctor_id);
        }

        $doctorIds = (clone $query)->pluck('dr_id')->filter()->unique()->values();

        $updatedCount = $query->where(function ($statusQuery) {
            $statusQuery->whereNull('varStatus')
                ->orWhere('varStatus', '!=', 'completed');
        })->update([
            'varStatus' => 'completed',
        ]);

        if ($doctorIds->isNotEmpty()) {
            User::whereIn('id', $doctorIds)->update(['monthly_payout' => 1]);
        }

        return redirect()->back()->with('success', "Marked {$updatedCount} records as paid for {$monthDate->format('F Y')}.");
    }

    private function buildMonthlySummaries($paymentLogs, float $commissionPercentage)
    {
        return $paymentLogs
            ->groupBy(function ($log) {
                $date = !empty($log->transaction_time) ? Carbon::parse($log->transaction_time) : Carbon::now();
                return $date->format('Y-m');
            })
            ->map(function ($logs, $monthKey) use ($commissionPercentage) {
                $appointmentIds = $logs->pluck('appointment_id')->filter()->unique();
                $grossAmount = (float) $logs
                    ->filter(fn ($log) => stripos((string) $log->varStatus, 'refund') === false)
                    ->sum('amount');

                $refundLogs = $logs->filter(fn ($log) => stripos((string) $log->varStatus, 'refund') !== false);
                $refundAmount = (float) $refundLogs->sum('amount');
                $commissionAmount = ($grossAmount * $commissionPercentage) / 100;
                $finalPayout = $grossAmount - $commissionAmount - $refundAmount;
                $completedTransfers = $logs->filter(fn ($log) => strtolower((string) $log->varStatus) === 'completed')->count();

                return [
                    'month_key' => $monthKey,
                    'month_label' => Carbon::createFromFormat('Y-m', $monthKey)->format('F Y'),
                    'appointment_count' => $appointmentIds->count(),
                    'transaction_count' => $logs->count(),
                    'gross_amount' => $grossAmount,
                    'commission_percentage' => $commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'refund_count' => $refundLogs->count(),
                    'refund_amount' => $refundAmount,
                    'final_payout' => $finalPayout,
                    'completed_transfers' => $completedTransfers,
                ];
            })
            ->sortByDesc('month_key')
            ->values();
    }

    private function getCommissionPercentage(): float
    {
        return (float) (GeneralSetting::where('field_name', 'percentage')->value('field_value') ?? 0);
    }
}
