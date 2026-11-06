<?php

namespace App\Http\Controllers;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NotificationController extends Controller
{
    /** Seconds allowed to reach Google, and for a whole call; a push never holds up an API for long. */
    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 5;

    private string $serviceAccountPath;

    /**
     * Send an FCM push notification.
     *
     * Title/body are sent in both `notification` (system tray) and `data`
     * so mobile apps can read them in foreground and local-notification handlers.
     *
     * @param  string  $token  Device FCM token
     * @param  string  $title  Notification title
     * @param  string  $body   Notification body
     * @param  string  $appType  'doctor' or 'patient'
     * @param  array   $data   Extra data payload (all values cast to string)
     * @param  string  $action Logical action name for logging (e.g. appointment_accepted)
     */
    public function sendPushNotification($token, $title, $body, $appType, array $data = [], string $action = 'unknown')
    {
        if (empty($token)) {
            Log::warning('Push notification skipped: no device token.', [
                'action' => $action,
                'app_type' => $appType,
                'title' => $title,
                'body' => $body,
            ]);

            return response()->json(['message' => 'No device token provided.'], 400);
        }

        try {
            $this->serviceAccountPath = match ($appType) {
                'doctor' => 'firebase/doctor-app-firebase-adminsdk.json',
                'patient' => 'firebase/patient-app-firebase-adminsdk.json',
                default => throw new \InvalidArgumentException('Invalid app type specified.'),
            };

            // Always include title/body in data so foreground handlers can display them.
            $dataPayload = array_map('strval', array_merge([
                'title' => (string) $title,
                'body' => (string) $body,
                'action' => $action,
            ], $data));

            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $dataPayload,
            ];

            Log::info('Sending push notification.', [
                'action' => $action,
                'app_type' => $appType,
                'title' => $title,
                'body' => $body,
                'data' => $dataPayload,
                'token_prefix' => substr((string) $token, 0, 12).'...',
            ]);

            $accessToken = $this->getAccessToken();
            $projectId = $this->getProjectId();

            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)->timeout(self::TIMEOUT)->withHeaders([
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
                    'action' => $action,
                    'app_type' => $appType,
                    'title' => $title,
                    'body' => $body,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json(['error' => 'Failed to send notification.'], $response->status());
            }

            Log::info('Push notification sent successfully.', [
                'action' => $action,
                'app_type' => $appType,
                'title' => $title,
                'body' => $body,
                'fcm_response' => $response->json(),
            ]);

            return response()->json(['message' => 'Notification sent successfully.']);
        } catch (\Exception $e) {
            Log::error('Error sending push notification: '.$e->getMessage(), [
                'action' => $action,
                'app_type' => $appType,
                'title' => $title,
                'body' => $body,
                'service_account' => $this->serviceAccountPath ?? null,
            ]);

            return response()->json(['error' => 'An error occurred while sending the notification.'], 400);
        }
    }

    /**
     * Google's access token lasts an hour, so it is cached and reused instead of being
     * fetched again for every push (that extra round trip made each push slow).
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'fcm_access_token:'.md5($this->serviceAccountPath);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentialsPath = storage_path('app/'.$this->serviceAccountPath);

        if (! is_file($credentialsPath)) {
            throw new RuntimeException("Firebase service account file not found: {$this->serviceAccountPath}");
        }

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            $credentialsPath
        );

        // Without a timeout a slow Google endpoint would hang the caller indefinitely.
        $accessToken = $credentials->fetchAuthToken(HttpHandlerFactory::build(new GuzzleClient([
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout' => self::TIMEOUT,
        ])));

        if (! isset($accessToken['access_token'])) {
            throw new RuntimeException('Failed to obtain Firebase access token.');
        }

        // Renew five minutes before Google's expiry.
        $ttl = max(60, (int) ($accessToken['expires_in'] ?? 3600) - 300);
        Cache::put($cacheKey, $accessToken['access_token'], $ttl);

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
