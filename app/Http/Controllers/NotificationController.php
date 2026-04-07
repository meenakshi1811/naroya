<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotificationJob;

class NotificationController extends Controller
{
    public function sendPushNotification($token, $title, $body, $appType)
    {
        dispatch(new SendPushNotificationJob($token, $title, $body, $appType));

        return response()->json(['message' => 'Notification queued successfully.'], 202);
    }





  
}
