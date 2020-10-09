<?php
namespace App\Http\Proxy;

use Ixudra\Curl\Facades\Curl;
abstract class BaseProxy implements IBaseProxy
{

    public function sendPost($url, $header, $payload){

        try {

            $response = Curl::to($url)
                ->withHeaders($header)
                ->withContentType('application/json')
                ->returnResponseObject()
                ->withData($payload)
                ->withTimeout(60)
                ->asJson()
                ->post();

            if($response && $response->status == 200){
                \App\LogActivity::addLog(
                    $url,
                    'external',
                    "API call",
                    self::class,
                    200,
                    'success',
                    "API call to $url",
                    json_encode($payload),
                    json_encode($response)
                );

                return $response;
            }else{
                \App\LogActivity::addLog(
                    $url,
                    'external',
                    "API call",
                    self::class,
                    400,
                    'fail',
                    "API call to $url",
                    json_encode($payload),
                    json_encode($response)
                );
                return $response;
            }
        }catch (\Exception $e){
            \App\LogActivity::addLog(
                $url,
                'external',
                "API call",
                self::class,
                400,
                'fail',
                "API call to $url",
                "",
                json_encode($e->getMessage())
            );
//            Log::error('Error sending Post request: '.$e->getMessage());
            return false;
        }

    }

    public function sendPostPayStack($url, $header, $payload){

        try {
            $response = Curl::to($url)
                ->withHeaders($header)
//                ->withContentType('application/json')
                ->withData(($payload))
                ->asJsonResponse()
                ->post();

            if($response){
                \App\LogActivity::addLog(
                    "",
                    'external',
                    "API call",
                    self::class,
                    200,
                    'success',
                    "API call to $url",
                    json_encode($payload),
                    json_encode($response)
                );
//                Log::info('Response Post request : '.['url'=>$url, 'data'=>json_encode($response)]);
                return $response;
            }else{
                \App\LogActivity::addLog(
                    "",
                    'external',
                    "API call",
                    self::class,
                    400,
                    'fail',
                    "API call to $url",
                    json_encode($payload),
                    json_encode($response)
                );
//                Log::error('Error sending Post request: '.['url'=>$url, 'data'=>json_encode($response)]);
                return false;
            }
        }catch (\Exception $e){
            \App\LogActivity::addLog(
                "",
                'external',
                "API call",
                self::class,
                400,
                'fail',
                "API call to $url",
                "",
                json_encode($e->getMessage())
            );
//            Log::error('Exception sending Post request: '.['url'=>$url, 'data'=>$e->getMessage()]);
            return false;
        }

    }

    public function sendGet($url, $header){
        try {
            $response = Curl::to($url)
                ->withHeaders($header)
                ->withContentType('application/json')
                ->asJson()
                ->returnResponseObject()
                ->get();
            if($response){
                \App\LogActivity::addLog(
                    $url,
                    'external',
                    "API call",
                    self::class,
                    200,
                    'success',
                    "API call to $url",
                    "",
                    json_encode($response)
                );
//                Log::info('Response from GET request : '.['url'=>$url, 'data'=>json_encode($response)]);
                return $response;
            }else{
                \App\LogActivity::addLog(
                    $url,
                    'external',
                    "API call",
                    self::class,
                    400,
                    'fail',
                    "API call to $url",
                    $url,
                    json_encode($response)
                );
//                Log::error('Error sending Post request: '.['url'=>$url, 'data'=>json_encode($response)]);
                return false;
            }
        }catch (\Exception $e){
            \App\LogActivity::addLog(
                $url,
                'external',
                "API call",
                self::class,
                400,
                'fail',
                "API call to $url",
                "",
                json_encode($e->getMessage())
            );

//            Log::error('Exception sending Post request: '.['url'=>$url, 'message'=>$e->getMessage()]);
            return false;
        }
    }
}
