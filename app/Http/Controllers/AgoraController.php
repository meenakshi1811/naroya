<?php

namespace App\Http\Controllers;

use App\Services\AgoraService;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\VideoSession;
use Exception;
use Illuminate\Support\Facades\Http;

class AgoraController extends Controller
{
    protected $agoraService;
    private $serviceAccountPath = 'firebase/wellora-ltd-firebase-adminsdk-npep8-88debc3422.json';

    public function __construct(AgoraService $agoraService)
    {
        $this->agoraService = $agoraService;
    }

    public function generateAgoraDetails(Request $request)
    {
        try {
            $request->validate([
                'appointment_id' => 'required|integer|exists:appointment,id',
            ]);

            $appointmentId = $request->appointment_id;
            $requestingUserId = $request->input('requesting_user_id');
            $requestingPatientId = $request->input('requesting_patient_id');

            
            $appointment = Appointment::with(['doctor', 'patient'])
                ->findOrFail($appointmentId);

            // Payment check
            if ($appointment->charIsPaid === 'N') {
                return response()->json([
                    'message' => 'Payment is pending!',
                    'data' => ['error' => 'Payment is pending'],
                ], 400);
            }

            $doctor = $appointment->doctor;
            $patient = $appointment->patient;

            $doctorId = $doctor->id;
            $patientId = $patient->id;

            // Channel
            $channelName = $this->generateChannelName($doctorId, $patientId);

            $doctorUid = $doctorId;
            $patientUid = $patientId;

            // Tokens
            $expiryTime = config('agora.token_expiry', 3600);

            $doctorToken = $this->agoraService->generateToken($channelName, $doctorUid, $expiryTime);
            $patientToken = $this->agoraService->generateToken($channelName, $patientUid, $expiryTime);

            // Save session (Model instead of DB)
            VideoSession::create([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'channel_name' => $channelName,
                'doctor_token' => $doctorToken,
                'patient_token' => $patientToken,
            ]);

            // Notification logic
            if (!empty($requestingUserId) && $requestingUserId == $doctorId) {
                $this->notifyPatient($patient, $channelName, $patientToken, $patientUid);
            } elseif (!empty($requestingPatientId) && $requestingPatientId == $patientId) {
                $this->notifyDoctor($doctor, $channelName, $doctorToken, $doctorUid);
            } else {
                return response()->json([
                    'error' => 'Invalid Request!'
                ], 400);
            }

           
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

   
    private function generateChannelName($doctorId, $patientId)
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', "doctor_{$doctorId}_patient_{$patientId}");
    }

    private function notifyPatient($patient, $channelName, $token, $uid)
    {
        if (!empty($patient->fcm_token)) {
            $this->sendFCMNotification(
                $patient->fcm_token,
                'Doctor Started the Meeting',
                'Your doctor has started the video call. Please join now.',
                $channelName,
                $token,
                $uid,
                'patient'
            );
        }
    }

    
    private function notifyDoctor($doctor, $channelName, $token, $uid)
    {
        if (!empty($doctor->fcm_token)) {
            $this->sendFCMNotification(
                $doctor->fcm_token,
                'Patient Started the Meeting',
                'Your patient has started the video call. Please join now.',
                $channelName,
                $token,
                $uid,
                'doctor'
            );
        }
    }

    
    private function sendFCMNotification($deviceToken, $title, $body, $channelId, $token, $uid, $appType)
    {
        try {
            $this->serviceAccountPath = match ($appType) {
                'doctor' => 'firebase/doctor-app-firebase-adminsdk.json',
                'patient' => 'firebase/patient-app-firebase-adminsdk.json',
                default => throw new \Exception('Invalid app type specified.')
            };

            $accessToken = $this->getAccessToken();

            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                        'type' => 'meeting',
                        'channelId' => (string) $channelId,
                        'token' => (string) $token,
                        'uid' => (string) $uid,
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/" . env('FIREBASE_PROJECT_ID') . "/messages:send",
                $payload
            );

            if (!$response->successful()) {
                \Log::error('FCM Error: ' . $response->body());
            }

        } catch (\Exception $e) {
            \Log::error('FCM Exception: ' . $e->getMessage());
        }
    }

    private function getAccessToken()
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/' . $this->serviceAccountPath));
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');

        return $client->fetchAccessTokenWithAssertion()['access_token'];
    }
}