<?php


namespace App\Traits;


trait Data
{

    public function dataLookup($payload){
//        $Hashed_Utf_Encoded_String = hash('sha512', utf8_encode(json_encode($requestBody)));
//        $keyPlusUsername = $key.$username;
//        $Token = base64_encode($keyPlusUsername);
//        $Signature = hash_hmac('sha256', $Hashed_Utf_Encoded_String, $Token, true);
//        $Authorization = strtoupper($username) . "-" . base64_encode($Signature . $date . $organisationCode);
//
        $curlService = new \Ixudra\Curl\CurlService();
        $data = $curlService->to(env('BASE_URL').'vas/data/lookup')
//            ->withHeader('token: '.$token)
            ->withData($payload)
            ->asJson()
            ->post();

        return $data;
    }

}
