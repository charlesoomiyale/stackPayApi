<?php

namespace App\Http\Controllers;

use App\Http\Proxy\BaxiProxy;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class CommonController extends ApiController
{

    private $BaxiProxy;

    public function __construct(JWTAuth $jwt,BaxiProxy $baxiProxy)
    {
        $this->BaxiProxy = $baxiProxy;
        $this->middleware('auth:api');
        $this->jwt = $jwt;
    }


    public function nameFinder(Request $request){
        $valid = Validator::make($request->all(), [
            'service_type' => 'required|string',
            'account_number' => 'required|string'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }

        $response = $this->BaxiProxy->nameFinder(['service_type' => $request->get('service_type'), 'account_number' =>$request->get('account_number') ]);


        if(isset($response->content->data)) {
            if ($response) {
                return response()->json(
                    [
                        'message' => 'Data found',
                        'data' => $response->content->data,
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Error querying account number. Try again.',
                        'data' => null,
                        'status' => '400'],
                    400);
            }
        }else{
            return response()->json(
                [
                    'message' => 'Error querying account number. Try again.',
                    'data' => $response,
                    'status' => '400'],
                400);
        }
    }




}
