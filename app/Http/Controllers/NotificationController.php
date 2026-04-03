<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Client;

class NotificationController extends Controller
{
    // Path to Firebase service account JSON file
   private $serviceAccountPath = 'firebase/wellora-ltd-firebase-adminsdk-npep8-88debc3422.json';


  public function sendPushNotification($token, $title, $body, $appType)
    {
        try {
            // Set service account JSON file based on app type
            if ($appType === 'doctor') {
                $this->serviceAccountPath = 'firebase/doctor-app-firebase-adminsdk.json';
            } elseif ($appType === 'patient') {
                $this->serviceAccountPath = 'firebase/patient-app-firebase-adminsdk.json';
            } else {
                throw new \Exception('Invalid app type specified.');
            }
    
            // Generate access token using the correct service account JSON file
            $accessToken = $this->getAccessToken();
    
            // Send the notification via FCM
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/v1/projects/" . env('FIREBASE_PROJECT_ID') . "/messages:send",
                [
                    'validate_only' => false,
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                    ],
                ]
            );
    
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



    //     public function sendPushNotification($deviceToken, $title, $body)
// {
//     $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
//     $serverKey = env('FCM_SERVER_KEY'); // Add your Firebase server key in the .env file

//     $data = [
//         'to' => $deviceToken,
//         'notification' => [
//             'title' => $title,
//             'body' => $body,
//             'sound' => 'default',
//         ],
//     ];

//     $headers = [
//         'Authorization: key=' . $serverKey,
//         'Content-Type: application/json',
//     ];

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $fcmUrl);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

//     $result = curl_exec($ch);
//     curl_close($ch);

//     return $result;
// }
}
