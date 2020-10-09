<?php

namespace App\Http\Controllers;



use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use \Illuminate\Http\Request;

class VtuController extends Controller
{
    private $curlService;

    public function __construct()
    {
        $this->curlService = new \Ixudra\Curl\CurlService();
    }

    public function dataLookUp(Request $request){

        $valid = Validator::make($request->all(), [
            'service' => 'required|string'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }

        $payload = [
            "service" => $request->get('service')
        ];

        $response = $this->curlService->to(env('BASE_URL').'vas/data/lookup')
//            ->withHeader('token: '.$token)
            ->withData($payload)
            ->asJson()
            ->post();


        if(isset($response)) {
            if ($response) {
                return response()->json(
                    [
                        'message' => 'Retrieved data',
                        'data' => $response->data,
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to retrive data. Try again.',
                        'data' => [],
                        'status' => '400'],
                    400);
            }

        }
    }



    public function subscribe(Request $request){

        $valid = Validator::make($request->all(), [
            'service' => 'required|string'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }

        $payload = [
            "terminal_id" => $request->get('walletid'),
            "user_id" => $request->get('service'),
            "password" => $request->get('password'),
            "pin" => $request->get('pin'),
            "service" => $request->get('service'),
            "phone" => $request->get('service'),
            "amount" => $request->get('amount'),
            "description" => $request->get('description'),
            "code" => $request->get('code'),
            "clientReference" => $request->get('clientReference')
        ];

        $response = $this->curlService->to(env('BASE_URL').'vas/data/lookup')
//            ->withHeader('token: '.$token)
            ->withData($payload)
            ->asJson()
            ->post();


        if(isset($response)) {
            if ($response) {
                return response()->json(
                    [
                        'message' => 'Retrieved data',
                        'data' => $response->data,
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to retrive data. Try again.',
                        'data' => [],
                        'status' => '400'],
                    400);
            }

        }
    }


}
