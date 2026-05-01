<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\BookCount;
use App\Models\DoctorCredit;
use App\Models\Patients;
use App\Models\PaymentLog;
use App\Models\User;
use App\Http\Controllers\NotificationController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 30;

    public function __construct(private readonly array $event) {}

    public function handle(): void
    { Log::info('Processing Stripe webhook event.', ['type' => $this->event['type'] ?? 'unknown']);
        $eventType = $this->event['type'] ?? null;
        $object    = $this->event['data']['object'] ?? [];

        if (! $eventType || ! is_array($object)) {
            Log::warning('Stripe webhook: invalid payload.', ['event' => $this->event]);
            return;
        }

        match ($eventType) {
            'payment_intent.succeeded'       => $this->handlePaymentIntentSucceeded($object),
            'payment_intent.payment_failed'  => $this->handlePaymentIntentFailed($object),
            'charge.refunded'                => $this->handleChargeRefunded($object),
            default => Log::info('Stripe webhook: event ignored.', ['type' => $eventType]),
        };
    }

    // ─────────────────────────────────────────────
    // payment_intent.succeeded
    // ─────────────────────────────────────────────
    private function handlePaymentIntentSucceeded(array $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;
        if (! $paymentIntentId) return;

        // ✅ Fetch from DB instead of metadata
        $paymentLog = PaymentLog::where('payment_id', $paymentIntentId)->first();

        if (! $paymentLog) {
            Log::error('Webhook: payment log not found.', ['id' => $paymentIntentId]);
            return;
        }

        // Idempotency
        if ($paymentLog->varStatus === 'succeeded') {
            Log::info('Already processed', ['id' => $paymentIntentId]);
            return;
        }

        $appointment = Appointment::find($paymentLog->appointment_id);
        $doctor      = User::find($paymentLog->dr_id);
        $patient     = Patients::find($paymentLog->patient_id);

        if (! $appointment || ! $doctor || ! $patient) {
            Log::error('Data missing in DB mapping');
            return;
        }

        $totalAmount  = ($paymentIntent['amount'] ?? 0) / 100;
        $adminFee     = ($paymentIntent['application_fee_amount'] ?? 0) / 100;
        $doctorAmount = $totalAmount - $adminFee;

        DB::transaction(function () use (
            $paymentLog, $paymentIntent, $appointment, $doctor, $patient,
            $doctorAmount, $adminFee
        ) {

            // ✅ Update initial log → mark as succeeded
            $paymentLog->update([
                'varStatus' => 'succeeded',
                'response'  => json_encode($paymentIntent),
            ]);

            // ✅ Doctor payment log (NEW — THIS WAS MISSING)
            PaymentLog::create([
                'patient_id'       => $paymentLog->patient_id,
                'dr_id'            => $paymentLog->dr_id,
                'appointment_id'   => $paymentLog->appointment_id,
                'amount'           => $doctorAmount,
                'payment_id'       => $paymentIntent['id'],
                'varStatus'        => 'succeeded',
                'transaction_time' => now(),
                'response'         => json_encode($paymentIntent),
                'description'      => 'Payment to doctor account',
            ]);

            // ✅ Admin commission log
            PaymentLog::create([
                'patient_id'       => $paymentLog->patient_id,
                'dr_id'            => null,
                'appointment_id'   => $paymentLog->appointment_id,
                'amount'           => $adminFee,
                'payment_id'       => $paymentIntent['id'],
                'varStatus'        => 'succeeded',
                'transaction_time' => now(),
                'response'         => json_encode($paymentIntent),
                'description'      => 'Commission earned by admin',
            ]);

            // ✅ Update appointment
            $appointment->update(['charIsPaid' => 'Y']);

            // ✅ Doctor credit
            DoctorCredit::updateOrCreate(
                ['dr_id' => $doctor->id],
                ['amount' => DB::raw("amount + {$doctorAmount}")]
            );
        });
    }

    // ─────────────────────────────────────────────
    // payment_intent.payment_failed
    // ─────────────────────────────────────────────
    private function handlePaymentIntentFailed(array $paymentIntent): void
    {
        $paymentIntentId = $paymentIntent['id'] ?? null;
        if (! $paymentIntentId) return;

        $metadata  = $paymentIntent['metadata'] ?? [];
        $patientId = $metadata['patient_id'] ?? null;
        $doctorId  = $metadata['doctor_id'] ?? null;

        // Log the failure
        PaymentLog::create([
            'patient_id'       => $patientId,
            'dr_id'            => $doctorId,
            'appointment_id'   => $metadata['appointment_id'] ?? null,
            'amount'           => ($paymentIntent['amount'] ?? 0) / 100,
            'payment_id'       => $paymentIntentId,
            'varStatus'        => 'failed',
            'transaction_time' => now(),
            'response'         => json_encode($paymentIntent),
            'description'      => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
        ]);

        // Notify patient
        $patient = $patientId ? Patients::find($patientId) : null;
        if ($patient && ! empty($patient->fcm_token)) {
            (new NotificationController())->sendPushNotification(
                $patient->fcm_token,
                'Payment Failed',
                'Your payment could not be processed. Please try again.',
                'patient'
            );
        }
    }

    // ─────────────────────────────────────────────
    // charge.refunded
    // ─────────────────────────────────────────────
    private function handleChargeRefunded(array $charge): void
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;
        $refundId        = $charge['refunds']['data'][0]['id'] ?? null;

        if (! $paymentIntentId) return;

        // Guard — skip if refund log already exists (created by processRefund() API call)
        if ($refundId && PaymentLog::where('payment_id', $refundId)->exists()) {
            Log::info('Webhook: charge.refunded already logged.', ['refund_id' => $refundId]);
            return;
        }

        $paymentLog = PaymentLog::where('payment_id', $paymentIntentId)
            ->whereNotNull('dr_id')
            ->first();

        if (! $paymentLog) return;

        PaymentLog::where('payment_id', $paymentIntentId)
            ->update(['varStatus' => 'refunded']);

        if ($refundId) {
            $refundAmount = (($charge['amount_refunded'] ?? 0) / 100) * -1;

            PaymentLog::create([
                'patient_id'       => $paymentLog->patient_id,
                'dr_id'            => $paymentLog->dr_id,
                'appointment_id'   => $paymentLog->appointment_id,
                'amount'           => $refundAmount,
                'payment_id'       => $refundId,
                'varStatus'        => 'refunded',
                'transaction_time' => now(),
                'response'         => json_encode($charge),
                'description'      => 'Refund processed',
            ]);
        }

        if ($paymentLog->appointment_id) {
            Appointment::where('id', $paymentLog->appointment_id)
                ->update(['charIsPaid' => 'N']);
        }

        // Notify patient
        $patient = Patients::find($paymentLog->patient_id);
        if ($patient && ! empty($patient->fcm_token)) {
            (new NotificationController())->sendPushNotification(
                $patient->fcm_token,
                'Refund Processed',
                'Your refund has been successfully processed.',
                'patient'
            );
        }
    }
}