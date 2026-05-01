<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Patients;
use Illuminate\Support\Facades\Http;

class SendMeetingReminder extends Command
{
    protected $signature = 'meeting:reminder';
    protected $description = 'Send push notifications 15 minutes before the meeting';
    private $serviceAccountPath = 'firebase/wellora-ltd-firebase-adminsdk-npep8-88debc3422.json';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $now = Carbon::now();
        $reminderTime = $now->addMinutes(15)->format('Y-m-d H:i:00'); // Get reminder timestamp
        
        $date = Carbon::now()->format('Y-m-d'); // Get today's date
        $time = Carbon::now()->addMinutes(15)->format('H:i'); // Get time 15 mins from now

// Get appointments where the date matches today's date and time matches 15 mins from now
$appointments = Appointment::where('varAppointment', $date)
                           ->where('startTime', $time)
                           ->where('charIsPaid', 'Y')
                           ->where('chrIsCanceled', 'N')
                           ->get();
                           

        foreach ($appointments as $appointment) {
            $doctor = User::find($appointment->dr_id);
            $patient = Patients::find($appointment->patient_id);

            if ($doctor && !empty($doctor->fcm_token)) {
                $this->sendFCMNotification(
                    $doctor->fcm_token,
                    "Upcoming Meeting",
                    "Your appointment starts in 15 minutes!",
                    $appointment->id,
                    'doctor'
                );
            }

            if ($patient && !empty($patient->fcm_token)) {
                $this->sendFCMNotification(
                    $patient->fcm_token,
                    "Upcoming Meeting",
                    "Your appointment starts in 15 minutes!",
                    $appointment->id,
                    'patient'
                );
            }
        }

        // Log::info('Meeting reminders sent successfully.');
    }






    private function sendFCMNotification($deviceToken, $title, $body, $appointmentId,$appType)
    {
        try {
            // Log::info('in');
            // Set the correct service account path based on app type
            if ($appType === 'doctor') {
                $this->serviceAccountPath = 'firebase/doctor-app-firebase-adminsdk.json';
            } elseif ($appType === 'patient') {
                $this->serviceAccountPath = 'firebase/patient-app-firebase-adminsdk.json';
            } else {
                throw new \Exception('Invalid app type specified.');
            }
    
            // Generate access token using the correct service account JSON file
            $accessToken = $this->getAccessToken();
    
            // Prepare the payload for FCM
            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                       'type' => 'meeting_reminder',
                        'appointmentId' => (string) $appointmentId,
                    ],
                ],
            ];
    
            // Send the notification via FCM
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/" . env('FIREBASE_PROJECT_ID') . "/messages:send",
                $payload
            );
    
            // Handle response
            if ($response->successful()) {
                return response()->json(['message' => 'Notification sent successfully.'], 200);
            } else {
                \Log::error('Failed to send push notification: ' . $response->body());
                return response()->json(['error' => 'Failed to send notification.'], $response->status());
            }
        } catch (\Exception $e) {
            \Log::error('Error sending push notification: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while sending the notification.'], 400);
        }
    }
    
    private function getAccessToken()
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/' . $this->serviceAccountPath));
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');
        $accessToken = $client->fetchAccessTokenWithAssertion();
        return $accessToken['access_token'];
    }

}
