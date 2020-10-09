<?php

namespace App\Http\Proxy;


class NotificationProxy extends BaseProxy
{

    protected $header;
    public function __construct()
    {
        $this->header = ["Authorization: key = ".env('FCM_SERVER_KEY')];
    }


    public function sendFcmNotification($payload){

        return $this->sendPost(env('FCM_BASE_URL'), $this->header, $payload);
    }


}
