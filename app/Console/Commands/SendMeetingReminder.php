<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationController;
use App\Models\Appointment;
use App\Models\Patients;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMeetingReminder extends Command
{
    protected $signature = 'meeting:reminder';

    protected $description = 'Send push notifications 15 minutes before the meeting';

    public function handle()
    {
        $reminderAt = Carbon::now()->addMinutes(15);
        $date = $reminderAt->format('Y-m-d');
        $time = $reminderAt->format('H:i');

        $appointments = Appointment::where('varAppointment', $date)
            ->where('startTime', $time)
            ->where('charIsPaid', 'Y')
            ->where('chrIsCanceled', 'N')
            ->get();

        $notificationController = new NotificationController();

        foreach ($appointments as $appointment) {
            $doctor = User::find($appointment->dr_id);
            $patient = Patients::find($appointment->patient_id);

            if ($doctor && ! empty($doctor->fcm_token)) {
                $notificationController->sendPushNotification(
                    $doctor->fcm_token,
                    'Upcoming Meeting',
                    'Your appointment starts in 15 minutes!',
                    'doctor',
                    [
                        'type' => 'meeting_reminder',
                        'appointmentId' => (string) $appointment->id,
                    ],
                    'meeting_reminder'
                );
            }

            if ($patient && ! empty($patient->fcm_token)) {
                $notificationController->sendPushNotification(
                    $patient->fcm_token,
                    'Upcoming Meeting',
                    'Your appointment starts in 15 minutes!',
                    'patient',
                    [
                        'type' => 'meeting_reminder',
                        'appointmentId' => (string) $appointment->id,
                    ],
                    'meeting_reminder'
                );
            }
        }
    }
}
