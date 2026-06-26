<?php

namespace App\Http\Controllers;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NotificationController extends Controller
{
    private string $serviceAccountPath;

    public function sendPushNotification($token, $title, $body, $appType, array $data = [])
    {
        if (empty($token)) {
            return response()->json(['message' => 'No device token provided.'], 400);
        }

        try {
            $this->serviceAccountPath = match ($appType) {
                'doctor' => 'firebase/doctor-app-firebase-adminsdk.json',
                'patient' => 'firebase/patient-app-firebase-adminsdk.json',
                default => throw new \InvalidArgumentException('Invalid app type specified.'),
            };

            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ];

            if ($data !== []) {
                $message['data'] = array_map('strval', $data);
            }

            $accessToken = $this->getAccessToken();
            $projectId = $this->getProjectId();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post(
                'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send',
                [
                    'validate_only' => false,
                    'message' => $message,
                ]
            );

            if (! $response->successful()) {
                Log::error('Failed to send push notification.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json(['error' => 'Failed to send notification.'], $response->status());
            }

            return response()->json(['message' => 'Notification sent successfully.']);
        } catch (\Exception $e) {
            Log::error('Error sending push notification: '.$e->getMessage(), [
                'app_type' => $appType,
                'service_account' => $this->serviceAccountPath ?? null,
            ]);

            return response()->json(['error' => 'An error occurred while sending the notification.'], 400);
        }
    }

    private function getAccessToken(): string
    {
        $credentialsPath = storage_path('app/'.$this->serviceAccountPath);

        if (! is_file($credentialsPath)) {
            throw new RuntimeException("Firebase service account file not found: {$this->serviceAccountPath}");
        }

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            $credentialsPath
        );

        $accessToken = $credentials->fetchAuthToken(HttpHandlerFactory::build());

        if (! isset($accessToken['access_token'])) {
            throw new RuntimeException('Failed to obtain Firebase access token.');
        }

        return $accessToken['access_token'];
    }

    private function getProjectId(): string
    {
        $config = json_decode(
            file_get_contents(storage_path('app/'.$this->serviceAccountPath)),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return $config['project_id'];
    }
}