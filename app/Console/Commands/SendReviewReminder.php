<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Models\Appointment;
use App\Models\Patients;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReviewReminder extends Command
{
    protected $signature = 'appointment:review-reminder';

    protected $description = 'Send push notifications 15 minutes after a paid appointment ends, asking the patient to leave a review';

    public function handle(): void
    {
        $date = Carbon::now()->format('Y-m-d');
        $endTime = Carbon::now()->subMinutes(15)->format('H:i');

        $appointments = Appointment::where('varAppointment', $date)
            ->where('endTime', $endTime)
            ->where('charIsPaid', 'Y')
            ->where('chrIsCanceled', 'N')
            ->get();

        $notificationController = new NotificationController();

        foreach ($appointments as $appointment) {
            $patient = Patients::find($appointment->patient_id);

            if (! $patient || empty($patient->fcm_token)) {
                continue;
            }

            $notificationController->sendPushNotification(
                $patient->fcm_token,
                'How was your appointment?',
                'Please take a moment to review your doctor.',
                'patient',
                [
                    'type' => 'review_request',
                    'appointmentId' => (string) $appointment->id,
                    'doctorId' => (string) $appointment->dr_id,
                ]
            );
        }
    }
}
