<?php

namespace App\Http\Controllers;

use App\Services\AgoraService;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\VideoSession;
use Exception;

class AgoraController extends Controller
{
    protected $agoraService;

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
            (new NotificationController())->sendPushNotification(
                $patient->fcm_token,
                'Doctor Started the Meeting',
                'Your doctor has started the video call. Please join now.',
                'patient',
                [
                    'type' => 'meeting',
                    'channelId' => (string) $channelName,
                    'token' => (string) $token,
                    'uid' => (string) $uid,
                ],
                'doctor_started_meeting'
            );
        }
    }

    private function notifyDoctor($doctor, $channelName, $token, $uid)
    {
        if (!empty($doctor->fcm_token)) {
            (new NotificationController())->sendPushNotification(
                $doctor->fcm_token,
                'Patient Started the Meeting',
                'Your patient has started the video call. Please join now.',
                'doctor',
                [
                    'type' => 'meeting',
                    'channelId' => (string) $channelName,
                    'token' => (string) $token,
                    'uid' => (string) $uid,
                ],
                'patient_started_meeting'
            );
        }
    }
}