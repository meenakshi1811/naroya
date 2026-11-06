<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Models\Appointment;
use App\Models\ClinicTokenQueue;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sends the "your turn is coming up" pushes for clinic tokens that the token APIs
 * marked as due (token_reminder_sent_at). Done here, off the request, so Firebase
 * can never slow down the doctor's + / − taps. Places and the running token are
 * read at send time, so a reminder that waited a minute is still accurate.
 */
class SendClinicTokenReminders extends Command
{
    protected $signature = 'clinic:token-reminders';

    protected $description = 'Send push reminders to patients whose clinic token is coming up';

    /** Reminders still waiting after this long are dropped: the queue has moved on. */
    private const STALE_AFTER_MINUTES = 30;

    public function handle(): int
    {
        $today = Carbon::now()->toDateString();

        $pending = Appointment::query()
            ->whereNotNull('token_reminder_sent_at')
            ->whereNull('token_reminder_pushed_at')
            ->where('varAppointment', '<=', $today)
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        // Claim them first, so an overlapping run never pushes one twice.
        Appointment::whereIn('id', $pending->pluck('id'))
            ->whereNull('token_reminder_pushed_at')
            ->update(['token_reminder_pushed_at' => now()]);

        $staleBefore = Carbon::now()->subMinutes(self::STALE_AFTER_MINUTES);
        $due = $pending
            ->filter(fn (Appointment $appointment) => $appointment->varAppointment === $today
                && Carbon::parse($appointment->token_reminder_sent_at)->greaterThanOrEqualTo($staleBefore))
            ->load(['patient:id,fcm_token', 'clinic:id,name', 'doctor:id,name,surname']);

        $notifications = new NotificationController();

        foreach ($due as $appointment) {
            $this->remind($appointment, $notifications);
        }

        return self::SUCCESS;
    }

    private function remind(Appointment $appointment, NotificationController $notifications): void
    {
        $fcmToken = $appointment->patient?->fcm_token;

        if (empty($fcmToken) || ! $appointment->clinic || $appointment->token_no === null
            || $appointment->chrIsCanceled === 'Y' || $appointment->chrIsRejected === 'Y') {
            return;
        }

        $tokenNo = (int) $appointment->token_no;
        $runningToken = (int) ClinicTokenQueue::where('clinic_id', $appointment->clinic_id)
            ->where('doctor_id', $appointment->dr_id)
            ->where('queue_date', $appointment->varAppointment)
            ->value('current_token');

        // Already called (or the queue was reset): nothing to remind about.
        if ($runningToken === 0 || $runningToken >= $tokenNo) {
            return;
        }

        $ahead = Appointment::where('clinic_id', $appointment->clinic_id)
            ->where('dr_id', $appointment->dr_id)
            ->where('varAppointment', $appointment->varAppointment)
            ->where('chrIsCanceled', 'N')
            ->where('chrIsRejected', 'N')
            ->where('token_no', '>', $runningToken)
            ->where('token_no', '<', $tokenNo)
            ->count();

        $doctor = $appointment->doctor;
        $serving = 'Now serving token ' . $runningToken
            . ($doctor ? ' for Dr. ' . trim($doctor->name . ' ' . ($doctor->surname ?? '')) : '')
            . ' at ' . $appointment->clinic->name . '. ';

        $notifications->sendPushNotification(
            $fcmToken,
            $ahead === 0 ? "You're next!" : 'Your turn is coming up',
            $ahead === 0
                ? $serving . 'Your token ' . $tokenNo . ' is next, please be at the clinic.'
                : $serving . 'Your token ' . $tokenNo . ' is coming up with ' . $ahead
                    . ($ahead === 1 ? ' patient' : ' patients') . ' ahead of you. Please be available.',
            'patient',
            [
                'type' => 'clinic_token_reminder',
                'appointmentId' => $appointment->id,
                'clinicId' => $appointment->clinic_id,
                'doctorId' => $appointment->dr_id,
                'tokenNo' => $tokenNo,
                'runningToken' => $runningToken,
                'patientsAhead' => $ahead,
            ],
            'clinic_token_reminder'
        );
    }
}
