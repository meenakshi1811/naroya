<?php

namespace App\Jobs;

use Google\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $serviceAccountPath = 'firebase/wellora-ltd-firebase-adminsdk-npep8-88debc3422.json';

    public function __construct(
        private readonly string $token,
        private readonly string $title,
        private readonly string $body,
        private readonly string $appType
    ) {
    }

    public function handle(): void
    {
        if ($this->appType === 'doctor') {
            $this->serviceAccountPath = 'firebase/doctor-app-firebase-adminsdk.json';
        } elseif ($this->appType === 'patient') {
            $this->serviceAccountPath = 'firebase/patient-app-firebase-adminsdk.json';
        } else {
            Log::error('Invalid app type specified for push notification job.', [
                'app_type' => $this->appType,
            ]);
            return;
        }

        $accessToken = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post(
            'https://fcm.googleapis.com/v1/projects/'.env('FIREBASE_PROJECT_ID').'/messages:send',
            [
                'validate_only' => false,
                'message' => [
                    'token' => $this->token,
                    'notification' => [
                        'title' => $this->title,
                        'body' => $this->body,
                    ],
                ],
            ]
        );

        if (! $response->successful()) {
            Log::error('Failed to send push notification from queue.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
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