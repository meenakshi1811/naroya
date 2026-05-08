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
        $commissionPercentage = $this->getCommissionPercentage();
        $monthlySummaries = $this->buildMonthlySummariesForDoctors($startOfMonth, $endOfMonth, $commissionPercentage);

        return view('admin.payment-ledger', compact('monthlySummaries', 'commissionPercentage'));
    }

    public function showDoctorPaymentLedger($id)
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $selectedDoctor = User::select('id', 'name', 'surname', 'email')->find($id);
        $commissionPercentage = $this->getCommissionPercentage();
        $monthlySummaries = $this->buildMonthlySummariesForDoctors($startOfMonth, $endOfMonth, $commissionPercentage, (int) $id);

        return view('admin.payment-ledger', compact('selectedDoctor', 'monthlySummaries', 'commissionPercentage'));
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

        $paymentQuery = Payment::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        if ($request->filled('doctor_id')) {
            $paymentQuery->where('doctor_id', (int) $request->doctor_id);
        }

        $doctorIds = (clone $paymentQuery)->pluck('doctor_id')->filter()->unique()->values();
        $paymentUpdatedCount = $paymentQuery->update(['monthly_payout' => 1]);

        $logQuery = PaymentLog::query()
            ->whereBetween('transaction_time', [$startOfMonth, $endOfMonth]);

        if ($request->filled('doctor_id')) {
            $logQuery->where('dr_id', (int) $request->doctor_id);
        }

        $updatedCount = $logQuery->where(function ($statusQuery) {
            $statusQuery->whereNull('varStatus')
                ->orWhere('varStatus', '!=', 'completed');
        })->update([
            'varStatus' => 'completed',
        ]);

        if ($doctorIds->isNotEmpty()) {
            User::whereIn('id', $doctorIds)->update(['monthly_payout' => 1]);
        }

        if ($paymentUpdatedCount === 0 && $updatedCount === 0) {
            return redirect()->back()->with('error', "No payout records found to mark as paid for {$monthDate->format('F Y')}.");
        }

        return redirect()->back()->with('success', "Marked {$paymentUpdatedCount} payment records as paid for {$monthDate->format('F Y')}.");
    }

    private function buildMonthlySummariesForDoctors(Carbon $startOfMonth, Carbon $endOfMonth, float $commissionPercentage, ?int $doctorId = null)
    {
        $doctorsQuery = User::query()
            ->select('id', 'name', 'surname', 'monthly_payout')
            ->where('chrApproval', 'Y');

        if (!is_null($doctorId)) {
            $doctorsQuery->where('id', $doctorId);
        }

        $doctors = $doctorsQuery->orderBy('name')->get();
        $monthKey = $startOfMonth->format('Y-m');
        $monthLabel = $startOfMonth->format('F Y');

        return $doctors->map(function ($doctor) use ($startOfMonth, $endOfMonth, $commissionPercentage, $monthKey, $monthLabel) {
                $paymentRows = Payment::query()
                    ->leftJoin('appointment', 'payments.appointment_id', '=', 'appointment.id')
                    ->where('payments.doctor_id', $doctor->id)
                    ->whereBetween('payments.created_at', [$startOfMonth, $endOfMonth])
                    ->select([
                        'payments.status',
                        'payments.appointment_id',
                        'appointment.amount as appointment_amount',
                    ])
                    ->get();

                $appointmentCount = $paymentRows->pluck('appointment_id')->filter()->unique()->count();
                $transactionCount = $paymentRows->count();
                $grossAmount = (float) $paymentRows
                    ->filter(fn ($row) => strtolower((string) $row->status) === 'success')
                    ->sum(function ($row) {
                        return (float) ($row->appointment_amount ?? 0);
                    });

                $refundRows = $paymentRows->filter(function ($row) {
                    return str_contains(strtolower((string) $row->status), 'refund');
                });
                $commissionAmount = ($grossAmount * $commissionPercentage) / 100;
                $refundAmount = (float) $refundRows->sum(function ($row) {
                    return (float) ($row->appointment_amount ?? 0);
                });
                $finalPayout = $grossAmount - $commissionAmount - $refundAmount;
                $completedTransfers = PaymentLog::query()
                    ->where('dr_id', $doctor->id)
                    ->whereBetween('transaction_time', [$startOfMonth, $endOfMonth])
                    ->where('varStatus', 'completed')
                    ->count();

                return [
                    'doctor_id' => $doctor->id,
                    'doctor_name' => trim(($doctor->name ?? '') . ' ' . ($doctor->surname ?? '')) ?: 'Unknown Doctor',
                    'month_key' => $monthKey,
                    'month_label' => $monthLabel,
                    'appointment_count' => $appointmentCount,
                    'transaction_count' => $transactionCount,
                    'gross_amount' => $grossAmount,
                    'commission_percentage' => $commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'refund_count' => $refundRows->count(),
                    'refund_amount' => $refundAmount,
                    'final_payout' => $finalPayout,
                    'completed_transfers' => $completedTransfers,
                    'monthly_payout' => (int) ($doctor->monthly_payout ?? 0),
                ];
            })
            ->sortBy('doctor_name')
            ->values();
    }

    private function getCommissionPercentage(): float
    {
        return (float) (GeneralSetting::where('field_name', 'percentage')->value('field_value') ?? 0);
    }
}
