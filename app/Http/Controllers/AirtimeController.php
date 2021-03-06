<?php

namespace App\Http\Controllers;

use App\Http\Proxy\BaxiProxy;
use App\Mail\Receipt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class AirtimeController extends Controller
{

    private $BaxiProxy;

    public function __construct(JWTAuth $jwt, BaxiProxy $baxiProxy, Request $request)
    {
        $this->BaxiProxy = $baxiProxy;
        $this->middleware('auth:api',
            ['only' => [
                'purchase'
            ]]);
        $this->jwt = $jwt;
    }


    public function getProviders(){

        $providers = \App\Provider::where('category', '=', 'airtime')->get();
//        $providers = Cache::remember('airtime_providers', 60 * 60 * 24, function (){
//            return \App\Provider::where('category', '=', 'airtime')->get();
//        });

            if ($providers) {
                return response()->json(
                    [
                        'message' => 'Providers loaded',
                        'data' => $providers,
                        'status' => '200'],
                    200);
            } else {
                return response()->json(
                    [
                        'message' => 'Failed to load Providers. Try again.',
                        'data' => null,
                        'status' => '400'],
                    400);
            }
    }

    public function updateProviders(){

        $response = $this->BaxiProxy->getAirtimeProviders();

        if(isset($response->content->data->providers)) {

                //Update or insert Records
                foreach ($response->content->data->providers as $provider){
                    \App\Provider::updateOrCreate(
                        ['service_type' => $provider->service_type,'category' => 'airtime'],
                        [
                            'service_type' => $provider->service_type,
                            'shortname' => $provider->shortname,
                            'biller_id' => $provider->biller_id,
                            'product_id' => $provider->product_id,
                            'name' => $provider->name,
                            'plan' => json_encode($provider->plan),
                            'category' => 'airtime',
                            'bonus_available' => 1,
                            'bonus_point' => 0.5,
                        ]
                    );
                }
                $providers = \App\Provider::where('category', '=', 'airtime')->get();
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

    public function purchase(Request $request){

        $valid = Validator::make($request->all(), [
            'phone' => 'required|string',
            'amount' => 'required|integer',
            'plan' => 'required|string',
            'service_type' => 'required|string'
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
            'phone' =>  $request->get('phone'),
            'plan' =>  $request->get('plan'),
            'service_type' =>  $request->get('service_type'),
            'agentId' =>  env('BAXI_AGENT_ID'),
            'agentReference' =>  '',
            'amount' =>  $request->get('amount'),
        ];



        //Start transaction
        $transaction  = new \App\PurchaseTransaction();
        $transaction->user_id = $request->user()->id;
        $transaction->amount = $request->get('amount');
        $transaction->type = 'airtime';
        $transaction->service_type = $request->get('service_type');
        $transaction->phone = $request->get('phone');
        $transaction->agent_id = env('BAXI_AGENT_ID');
        $transaction->provider = 'BAXI-CAPRICORNDIGI';
        $transaction->status = 'initiated';
        $transaction->save();

        $payload['agentReference'] = $transaction->transaction_reference;

        $response = $this->BaxiProxy->purchaseAirtime($payload);

        if(isset($response->content->data)) {
            if ($response) {

                //Debit User
                $user = \App\User::find($request->user()->id)->first();
                $user->withdraw( $request->input('amount'),['narration'=>'airtime purchase']);

                //check for bonus
                $provider = \App\Provider::where('category', '=', 'airtime')->where( 'service_type' , '=',  $request->get('service_type'))->first();
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
                        strtoupper($request->get('service_type'))." Airtime top-up",
                        "airtime",
                        $request->get('phone'),
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
                return response()->json(
                    [
                        'message' => 'Recharge Failed',
                        'data' => null,
                        'status' => '400'],
                    400);
            }

        }else{
            //Update failed Transaction
            \App\PurchaseTransaction::where('transaction_reference', $payload['agentReference'])
                ->update(['status' => 'failed']);
            return response()->json(
                [
                    'message' => 'Recharge Failed. Network Error',
                    'data' => null,
                    'status' => '400'
                ],
                400);
        }
    }




}
