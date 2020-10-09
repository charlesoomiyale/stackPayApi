<?php


namespace App\Traits;


trait Bills
{

    public function checkoutBills($token, $payload){
        $curlService = new \Ixudra\Curl\CurlService();
        $data = $curlService->to(env('BASE_URL').'vas/data/lookup')
            ->withHeader('token: '.$token)
            ->withData($payload)
            ->asJsonResponse()
            ->post();

        return $data;
    }

}
