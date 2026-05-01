<?php

namespace App\Services;

require_once app_path('Services/Agora/RtcTokenBuilder.php');

use App\Services\Agora\RtcTokenBuilder;
class AgoraService
{
    protected $appId;
    protected $appCertificate;

    public function __construct()
    {
        $this->appId = env('AGORA_APP_ID');
        $this->appCertificate = env('AGORA_APP_CERTIFICATE');
    }

    /**
     * Generate Agora token.
     *
     * @param string $channelName
     * @param string $uid
     * @param int $expiryTimeInSeconds
     * @return string
     */
    public function generateToken(string $channelName, string $uid, int $expiryTimeInSeconds = 3600): string
    {
        $currentTime = now()->timestamp;
        $privilegeExpireTime = $currentTime + $expiryTimeInSeconds;

        return RtcTokenBuilder::buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $uid,
            RtcTokenBuilder::RoleAttendee,
            $privilegeExpireTime
        );
    }
}
