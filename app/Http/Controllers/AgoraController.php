<?php

// namespace App\Http\Controllers;

// use App\Services\AgoraService;
// use Illuminate\Http\Request;
// use App\Models\Appointment;
// use DB;
// use Exception;

// class AgoraController extends Controller
// {
//     protected $agoraService;

//     public function __construct(AgoraService $agoraService)
//     {
//         $this->agoraService = $agoraService;
//     }

//     /**
//      * Generate and return an Agora token.
//      *
//      * @param Request $request
//      * @return \Illuminate\Http\JsonResponse
//      */
//     public function generateAgoraDetails(Request $request)
//     {
//         try {
//             $request->validate([
//                 'appointment_id' => 'required|integer|exists:appointment,id',
//             ]);
    
//             $appontmentId = $request->input('appointment_id');
//             $apointmentData = Appointment::where('id', $appontmentId)->firstOrFail();
    
//             if ($apointmentData->charIsPaid === 'N') {
//                 return response()->json([
//                     'message' => 'Payment is pending!',
//                     'data' => ['error' => 'Payment is pending'],
//                 ], 400);
//             }
    
//             $doctorId = $apointmentData->dr_id;
//             $patientId = $apointmentData->patient_id;
    
//             // Generate a unique channel name
//             $channelName = preg_replace('/[^a-zA-Z0-9_-]/', '', "doctor_{$doctorId}_patient_{$patientId}");
    
//             // Generate UIDs
//             $doctorUid = $doctorId;
//             $patientUid = $patientId;
    
//             // Generate tokens
//             $expiryTime = config('agora.token_expiry', 3600); // Use configured expiry time
//             $agoraService = new AgoraService();
//             $doctorToken = $agoraService->generateToken($channelName, $doctorUid, $expiryTime);
//             $patientToken = $agoraService->generateToken($channelName, $patientUid, $expiryTime);
    
//             // Save session in the database
//             DB::beginTransaction();
//             try {
//                 DB::table('video_sessions')->insert([
//                     'doctor_id' => $doctorId,
//                     'patient_id' => $patientId,
//                     'channel_name' => $channelName,
//                     'doctor_token' => $doctorToken,
//                     'patient_token' => $patientToken,
//                     'created_at' => now(),
//                 ]);
//                 DB::commit();
//             } catch (\Exception $e) {
//                 DB::rollBack();
//                 throw $e;
//             }
    
//             // Return response
//             return response()->json([
//                 'channel_name' => $channelName,
//                 'expiry_time' => $expiryTime,
//                 'doctor' => [
//                     'uid' => $doctorUid,
//                     'token' => $doctorToken,
//                 ],
//                 'patient' => [
//                     'uid' => $patientUid,
//                     'token' => $patientToken,
//                 ],
//             ], 200);
    
//         } catch (\Illuminate\Validation\ValidationException $e) {
//             return response()->json([
//                 'message' => 'Validation error!',
//                 'data' => ['error' => $e->errors()],
//             ], 400);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'message' => 'An error occurred while generating Agora details.',
//                 'data' => ['error' => $e->getMessage()],
//             ], 400);
//         }
//     }
    
// }

namespace App\Http\Controllers;

use App\Services\AgoraService;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patients;
use DB;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class AgoraController extends Controller
{
    protected $agoraService;
    private $serviceAccountPath = 'firebase/wellora-ltd-firebase-adminsdk-npep8-88debc3422.json';
    public function __construct(AgoraService $agoraService)
    {
        $this->agoraService = $agoraService;
        
    }

    /**
     * Generate Agora details and send FCM notification.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAgoraDetails(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'appointment_id' => 'required|integer|exists:appointment,id',              
            ]);
            $requestingUserId = '';
            $requestingpatientId = '';
            $appointmentId = $request->input('appointment_id');
            if(!empty($request->input('requesting_user_id'))){
                $requestingUserId = $request->input('requesting_user_id'); // New
            }
            if(!empty($request->input('requesting_patient_id'))){
                $requestingpatientId = $request->input('requesting_patient_id'); // New
            }
            $appointmentData = Appointment::where('id', $appointmentId)->firstOrFail();
    
            // Check if the payment is completed
            if ($appointmentData->charIsPaid === 'N') {
                return response()->json([
                    'message' => 'Payment is pending!',
                    'data' => ['error' => 'Payment is pending'],
                ], 400);
            }
    
            $doctorId = $appointmentData->dr_id;
            $patientId = $appointmentData->patient_id;
    
            $doctor = User::find($doctorId);
            $patient = Patients::find($patientId);
    
            // Generate a unique channel name
            $channelName = preg_replace('/[^a-zA-Z0-9_-]/', '', "doctor_{$doctorId}_patient_{$patientId}");
    
            // Generate UIDs
            $doctorUid = $doctorId;
            $patientUid = $patientId;
    
            // Generate Agora tokens
            $expiryTime = config('agora.token_expiry', 3600);
            $doctorToken = $this->agoraService->generateToken($channelName, $doctorUid, $expiryTime);
            $patientToken = $this->agoraService->generateToken($channelName, $patientUid, $expiryTime);
    
            // Save session in the database
            DB::beginTransaction();
            try {
                DB::table('video_sessions')->insert([
                    'doctor_id' => $doctorId,
                    'patient_id' => $patientId,
                    'channel_name' => $channelName,
                    'doctor_token' => $doctorToken,
                    'patient_token' => $patientToken,
                    'created_at' => now(),
                ]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
    
            // Determine who started the meeting and send notification to the other user
            if (!empty($requestingUserId) && $requestingUserId == $doctorId) {
                // Doctor started the meeting → Notify patient
                if (!empty($patient->fcm_token)) {
                    $this->sendFCMNotification(
                        $patient->fcm_token,
                        'Doctor Started the Meeting',
                        'Your doctor has started the video call. Please join now.',
                        $channelName,
                        $patientToken,
                        $patientUid,
                        'patient'
                    );
                }
            } else if(!empty($requestingpatientId) && $requestingpatientId == $patientId) { 
                // Patient started the meeting → Notify doctor
                if (!empty($doctor->fcm_token)) {
                    $this->sendFCMNotification(
                        $doctor->fcm_token,
                        'Patient Started the Meeting',
                        'Your patient has started the video call. Please join now.',
                        $channelName,
                        $doctorToken,
                        $doctorUid,
                        'doctor'
                    );
                }
            } else {
                return response()->json([
                    'error' => 'Invalid Request!'
                ], 400);
            }
    
            // Return response
            return response()->json([
                'channel_name' => $channelName,
                'expiry_time' => $expiryTime,
                'doctor' => [
                    'uid' => $doctorUid,
                    'token' => $doctorToken,
                ],
                'patient' => [
                    'uid' => $patientUid,
                    'token' => $patientToken,
                ],
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error!',
                'data' => ['error' => $e->errors()],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while generating Agora details.',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }
    


    /**
     * Send an FCM notification.
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @param string $channelId
     * @param string $token
     * @param int $uid
     * @return void
     */
    //  private function sendFCMNotification($deviceToken, $title, $body, $channelId, $token, $uid)
    // {
    //     $serverKey = env('FCM_SERVER_KEY'); // FCM server key from .env or config
    //     $url = 'https://fcm.googleapis.com/fcm/send';

    //     $payload = [
    //         'to' => $deviceToken,
    //         'notification' => [
    //             'title' => $title,
    //             'body' => $body,
    //         ],
    //         'data' => [
    //             'type' => 'meeting',
    //             'channelId' => $channelId,
    //             'token' => $token,
    //             'uid' => $uid,
    //         ],
    //     ];

    //     $headers = [
    //         'Authorization: key=' . $serverKey,
    //         'Content-Type: application/json',
    //     ];

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    //     $result = curl_exec($ch);
    //     dd($result);
    //     if ($result === false) {
    //         throw new Exception('FCM send error: ' . curl_error($ch));
    //             \Log::error('Error sending push notification: ' . $ch);
    //             return response()->json(['error' => 'An error occurred while sending the notification.'], 400);
    //     }

    //     curl_close($ch);
    // }
    
    private function sendFCMNotification($deviceToken, $title, $body, $channelId, $token, $uid, $appType)
{
    try {
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
                    'type' => 'meeting',
                    'channelId' => (string) $channelId,  // 🔹 Convert to string
                    'token' => (string) $token,          // 🔹 Convert to string
                    'uid' => (string) $uid,
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
