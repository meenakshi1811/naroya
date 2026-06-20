<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    private string $serviceAccountPath = 'firebase/noraya-88c87-firebase-adminsdk-fbsvc-8263e6f2c8.json';

    public function sendPushNotification($token, $title, $body, $appType, array $data = [])
    {
        if (empty($token)) {
            return response()->json(['message' => 'No device token provided.'], 400);
        }

        try {
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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post(
                'https://fcm.googleapis.com/v1/projects/'.env('FIREBASE_PROJECT_ID').'/messages:send',
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
            Log::error('Error sending push notification: '.$e->getMessage());

            return response()->json(['error' => 'An error occurred while sending the notification.'], 400);
        }
    }

    private function getAccessToken(): string
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/'.$this->serviceAccountPath));
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');
        $accessToken = $client->fetchAccessTokenWithAssertion();

        return $accessToken['access_token'];
    }
}