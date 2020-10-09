<?php

namespace App\Http\Controllers;

use App\Http\Proxy\BaxiProxy;
use App\Mail\Receipt;
use App\Transformers\BouquetsTransformer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use League\Fractal;

class CableTvController extends ApiController
{

//    private $curlService;
    private $BaxiProxy;

    public function __construct(JWTAuth $jwt,BaxiProxy $baxiProxy)
    {
        $this->BaxiProxy = $baxiProxy;
        $this->middleware('auth:api',
            ['only' => [
                'purchase'
            ]]);
        $this->jwt = $jwt;
    }


    public function getProviders(){

//        $providers = Cache::remember('tv_providers', 60 * 60 * 24, function (){
//            return \App\Provider::where('category', '=', 'tv')->get();
//        });

        $providers = \App\Provider::where('category', '=', 'tv')->get();
            if ($providers) {
                return response()->json(
                    [
                        'message' => 'Bouquets loaded',
                        'data' => $providers,
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to load Bouquets. Try again.',
                        'data' => null,
                        'status' => '400'],
                    400);
            }
    }

    public function updateProviders(){

        $response = $this->BaxiProxy->getTvProviders();

        if(isset($response->content->data->providers)) {

            //Update or insert Records
            foreach ($response->content->data->providers as $provider){
                \App\Provider::updateOrCreate(
                    ['service_type' => $provider->service_type,'category' => 'tv'],
                    [
                        'shortname' => $provider->shortname,
                        'biller_id' => $provider->biller_id,
                        'product_id' => $provider->product_id,
                        'name' => $provider->name,
                        'category' => 'tv',
                    ]
                );
            }
            $providers = \App\Provider::where('category', '=', 'tv')->get();
            return response()->json(
                [
                    'message' => 'Providers updated',
                    'data' => $providers,
                    'status' => '200'],
                200);
        }else{
            return response()->json(
                [
                    'message' => 'Failed to load Providers. Try again.',
                    'data' => $response,
                    'status' => '400'],
                400);
        }
    }

    public function getBouquets($service_type){

//        $bundles = Cache::remember($service_type, 60 * 60 * 24, function () use ($service_type) {
//            return \App\Bouquet::where('service_type', $service_type)->get();
//        });

        $bundles = \App\Bouquet::where('service_type', $service_type)->get();

            if ($bundles) {
                $resource = new Fractal\Resource\Collection($bundles,new BouquetsTransformer);
                $result = $this->apiResponse()->createData($resource)->toArray();
                return response()->json(
                    [
                        'message' => 'Bouquets loaded',
                        'data' => $result['data'],
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to load Bouquets. Try again.',
                        'data' => null,
                        'status' => '400'],
                    400);
            }
    }

    public function updateBouquets($service_type){

        $response = $this->BaxiProxy->getTvProvider(['service_type' => $service_type]);

        if(isset($response->content->data)) {

                foreach ($response->content->data as $provider){
                    \App\Bouquet::updateOrCreate(
                        ['service_type' => $service_type, 'code' => $provider->code],
                        [
                            'description' => $provider->description,
                            'name' => $provider->name,
                            'code' => $provider->code,
                            'availablePricingOptions' => json_encode($provider->availablePricingOptions),
                        ]
                    );
                }
                $bundles = \App\Bouquet::where('service_type', $service_type)->get();
                return response()->json(
                    [
                        'message' => 'Bouquets updated',
                        'data' => $bundles,
                        'status' => '200'],
                    200);
//                return response()->json(
//                    [
//                        'message' => 'Bouquets updated',
//                        'data' => $response->content->data,
//                        'status' => '200'],
//                    200);
        }else{
            return response()->json(
                [
                    'message' => 'Failed to load Bouquets. Try again.',
                    'data' => $response,
                    'status' => '400'],
                400);
        }
    }

    public function getBouquetAddons(Request $request){
        $valid = Validator::make($request->all(), [
            'service_type' => 'required|string',
            'product_code' => 'required|string'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }

        $response = $this->BaxiProxy->getTvProviderAddons(['service_type' => $request->get('service_type'), 'product_code' =>$request->get('product_code') ]);


        if(isset($response->content->data)) {
            if ($response) {
                return response()->json(
                    [
                        'message' => 'Bouquet Add-ons loaded',
                        'data' => $response->content->data,
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to load Bouquet Add-ons. Try again.',
                        'data' => null,
                        'status' => '400'],
                    400);
            }
        }else{
            return response()->json(
                [
                    'message' => 'Failed to load Bouquet Add-ons. Try again.',
                    'data' => $response,
                    'status' => '400'],
                400);
        }
    }

    public function purchase(Request $request){

        $valid = Validator::make($request->all(), [
            'amount' => 'required|integer',
            'service_type' => 'required|string',
            'product_code' => 'required|string',
            'product_monthsPaidFor' => 'required|integer',
            'smartcard_number' => 'required|string',
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }

        if ($request->input('amount') > $request->user()->balance){
            return response()->json(
                [
                    'message' => 'Insufficient balance',
                    'data' => null,
                    'status' => '400'],
                400);
        }

        //payload
        $payload = [
            'total_amount' =>  $request->get('amount'),
            'product_code' =>  $request->get('product_code'),
            'product_monthsPaidFor' =>  $request->get('product_monthsPaidFor'),
            'smartcard_number' =>  $request->get('smartcard_number'),
            'service_type' =>  $request->get('service_type'),
            'agentId' =>  env('BAXI_AGENT_ID'),
            'agentReference' =>  ''
        ];



        //Start transaction
        $transaction  = new \App\PurchaseTransaction();
        $transaction->user_id = $request->user()->id;
        $transaction->amount = $request->get('amount');
        $transaction->type = 'tv';
        $transaction->service_type = $request->get('service_type');
        $transaction->product_monthsPaidFor = $request->get('product_monthsPaidFor');
        $transaction->smartcard_number = $request->get('smartcard_number');
        $transaction->agent_id = env('BAXI_AGENT_ID');
        $transaction->provider = 'BAXI-CAPRICORNDIGI';
        $transaction->status = 'initiated';
        $transaction->save();

        $payload['agentReference'] = $transaction->transaction_reference;

        $response = $this->BaxiProxy->purchaseTv($payload);

        if ($response) {
        if(isset($response->content->data)) {
                //Debit User
                $user = \App\User::find($request->user()->id)->first();
                $user->withdraw( $request->input('amount'));
                //check for bonus
                $provider = \App\Provider::where('category', '=', 'tv')->where( 'service_type' , '=',  $request->get('service_type'))->first();
                if ($provider->bonus_available){
                    $bonus = floor($request->input('amount')/100 * $provider->bonus_point);
                    if ($bonus){
                        $user->deposit( $bonus,['narration'=>'purchase bonus']);
                    }
                }
                //Update Transaction
                \App\PurchaseTransaction::where('transaction_reference', $payload['agentReference'])
                    ->update(['status' => 'complete', 'baxi_transaction_ref' => $response->content->data->transactionReference]);

                Mail::to($request->user()->email)->send(new Receipt(
                        $request->user()->firstname,
                        strtoupper($request->get('service_type'))." subscription",
                        "tv",
                        $request->get('smartcard_number'),
                        $transaction->transaction_reference,
                        $transaction->amount
                    )
                );

                return response()->json(
                    [
                        'message' => 'Recharge Successful',
                        'data' => array_merge((array)$response->content->data,['balance' => $user->balance]),
                        'status' => '200'],
                    200);
            } else {
            if (isset($response->content->errors->smartcard_number)){
                return response()->json(
                    [
                        'message' => $response->content->errors->smartcard_number[0],
                        'data' => null,
                        'status' => '400'],
                    400);
            }else {
                return response()->json(
                    [
                        'message' => $response->content->message,
                        'data' => null,
                        'status' => '400'],
                    400);
            }
            }

        }else{
            //Update failed Transaction
            \App\PurchaseTransaction::where('transaction_reference', $payload['agentReference'])
                ->update(['status' => 'failed']);
            return response()->json(
                [
                    'message' => 'Recharge Failed',
                    'data' => $response,
                    'status' => '400'],
                400);
        }
    }




}
