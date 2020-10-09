<?php

namespace App\Jobs;

use App\Mail\AccountRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;


class SendOnboardEmail extends Job
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

//        Redis::throttle('onboard-email')->allow(2)->every(1)->then(function () {

            Mail::to($this->payload->email)
                ->send(new AccountRegistered($this->payload->name));

//        }, function () {
//            // Could not obtain lock; this job will be re-queued
//            return $this->release(2);
//        });

    }
}
