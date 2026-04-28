<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotificationJob;

class NotificationController extends Controller
{
    public function sendPushNotification($token, $title, $body, $appType)
    {
        SendPushNotificationJob::dispatchSync($token, $title, $body, $appType);

        return response()->json(['message' => 'Notification sent successfully.']);
    }





  
}
