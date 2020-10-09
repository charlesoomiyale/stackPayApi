<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


class SendNotification extends Job
{
    use  InteractsWithQueue, Queueable, SerializesModels;

    private $payload;

    /**
     * Create a new job instance.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->payload = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Redis::throttle('fcm-notification')->allow(2)->every(1)->then(function () {

            $curlService = new \Ixudra\Curl\CurlService();
            $curlService->to(env('FCM_BASE_URL'))
                ->withHeader("Authorization: key = ".env('FCM_SERVER_KEY'))
                ->withContentType('application/json')
                ->withData($this->payload)
                ->asJsonRequest()
                ->returnResponseObject()
                ->post();

        }, function () {
            // Could not obtain lock; this job will be re-queued
            return $this->release(2);
        });




    }
}
