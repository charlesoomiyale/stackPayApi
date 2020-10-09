<?php

namespace App\Http\Controllers;

use App\CardTokens;
use App\Enums\TransactionStatus;
use App\Http\Proxy\NotificationProxy;
use App\Jobs\SendNotification;
use App\Mail\Wallet;
use App\PayStackTransaction;
//use App\Mail\AmazonSes;
//use App\Transactions;
//use Artesaos\SEOTools\Facades\SEOMeta;
//use Illuminate\Support\Facades\Log;
//use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Http\Proxy\PayStackProxy;
use \Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use League\Fractal;
use App\Transformers\Auth\UserTransformer;

class WalletController extends ApiController
{

    private $curlService;
    protected $jwt;
    protected $notificationProxy;
    private $payStackProxy;
    public function __construct(JWTAuth $jwt,PayStackProxy $PayStackProxy, NotificationProxy $NotificationProxy)
    {
        $this->payStackProxy = $PayStackProxy;
        $this->curlService = new \Ixudra\Curl\CurlService();
        $this->middleware('auth:api');
        $this->jwt = $jwt;
        $this->notificationProxy = $NotificationProxy;
    }


    public function credit(Request $request){

        $valid = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'reference' => 'required|string'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }


        //Validate transaction
        if($request->has('reference')) {
            $ref = $request->get('reference');
            if(!PayStackTransaction::where('reference', $ref)->exists()){
                return response()->json(
                    [
                        'message' => 'Transaction not found',
                        'data' => null,
                        'status' => '400'],
                    400);
            }
            $verifyTrans = $this->payStackProxy->verifyTransaction($ref);
            if ($verifyTrans) {

                if(!$verifyTrans->content->status){
                    PayStackTransaction::where('reference', $ref)->update(
                        [
                            'status' =>
                                TransactionStatus::FAILED()
                        ]
                    );

                    return response()->json(
                        [
                            'message' => $verifyTrans->content->message,
                            'data' => null,
                            'status' => '400'],
                        400);

                }elseif ($verifyTrans->content->data->status == 'success') {

                    PayStackTransaction::where('reference', $ref)->update(
                        [
                            'status' =>TransactionStatus::COMPLETE(),
                            'description' => 'wallet_credit',
                            'gateway_response' =>$verifyTrans->content->data->gateway_response,
                            'message' =>$verifyTrans->content->data->message,
                            'channel' =>$verifyTrans->content->data->channel,
                            'paid_at' =>$verifyTrans->content->data->paid_at,
                            'created_at_ps' =>$verifyTrans->content->data->created_at,
                            'currency' =>$verifyTrans->content->data->currency,
                            'ip_address' =>$verifyTrans->content->data->ip_address,
                            'metadata' =>$verifyTrans->content->data->metadata,
                            'log' =>$verifyTrans->content->data->log,
                            'fees' =>$verifyTrans->content->data->fees,
                            'plan' =>$verifyTrans->content->data->plan,
                            'order_id' =>$verifyTrans->content->data->order_id,
                            'plan_object' => json_encode($verifyTrans->content->data->plan_object),
                            'subaccount' => json_encode($verifyTrans->content->data->subaccount),
                            'authorization' => json_encode($verifyTrans->content->data->authorization)
                            ]
                    );

                    $match = [
                        'bin' => $verifyTrans->content->data->authorization->bin,
                        'last4' => $verifyTrans->content->data->authorization->last4,
                        'exp_month' => $verifyTrans->content->data->authorization->exp_month,
                        'exp_year' => $verifyTrans->content->data->authorization->exp_year,
                        'channel' => $verifyTrans->content->data->authorization->channel,
                        'card_type' => $verifyTrans->content->data->authorization->card_type,
                        'bank' => $verifyTrans->content->data->authorization->bank,
                        'country_code' => $verifyTrans->content->data->authorization->country_code,
                        'brand' => $verifyTrans->content->data->authorization->brand,
                        'reusable' => $verifyTrans->content->data->authorization->reusable,
                        'signature' => $verifyTrans->content->data->authorization->signature,
                        'user_id' => $request->user()->id,
                    ];

                    CardTokens::updateOrCreate($match, ['authorization_code' => $verifyTrans->content->data->authorization->authorization_code]);

                    //Credit User
                    $user = \App\User::find($request->user()->id)->first();
                    $user->deposit( $request->input('amount'));
                    Mail::to($request->user()->email)->send(new Wallet(
                            $request->user()->firstname,
                            "You have successfully recharged your wallet with ".$request->input('amount') . " Naira",
                            "Wallet Top-up"
                        )
                    );
                    //Return response
                    return response()->json(
                        [
                            'message' => 'Wallet Top-up successful',
                            'data' => ['balance' => $user->balance],
                            'status' => '200'],
                        200);



                }
            }
            PayStackTransaction::where('reference', $ref)->update(
                [
                    'status' =>
                        TransactionStatus::FAILED()
                ]
            );

            return response()->json(
                [
                    'message' => 'Failed to complete payment',
                    'data' => null,
                    'status' => 'fail'],
                400);
        }


        return response()->json(
            [
                'message' => 'Transaction failed',
                'data' => null,
                'status' => 'fail'],
            400);


    }

    public function creditWithToken(Request $request){

        $valid = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'authorization_code' => 'required|string',
            'email' => 'required|string'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }


        //Validate transaction
        if($request->has('authorization_code')) {
            $authorization_code = $request->get('authorization_code');
//            if(!CardTokens::where('authorization_code', $authorization_code)->exists()){
//                return response()->json(
//                    [
//                        'message' => 'Card not found',
//                        'data' => null,
//                        'status' => '400'],
//                    400);
//            }
            $payload = [
                'amount'=> intval( $request->get('amount')),
                'email'=> $request->get('email'),
                'authorization_code' => $authorization_code
            ];
            $verifyTrans = $this->payStackProxy->chargeToken($payload);
            if ($verifyTrans) {

                if(!$verifyTrans->content->status){
                    PayStackTransaction::create(
                        [
                            'status' =>TransactionStatus::FAILED(),
                            'description' => 'wallet_credit',
                            'amount' => $request->input('amount'),
                            'gateway_response' =>$verifyTrans->content->data->gateway_response,
                            'message' =>$verifyTrans->content->data->message,
                            'reference' =>$verifyTrans->content->data->reference,
                            'channel' =>$verifyTrans->content->data->channel,
                            'paid_at' =>$verifyTrans->content->data->transaction_date,
                            'created_at_ps' =>$verifyTrans->content->data->transaction_date,
                            'currency' =>$verifyTrans->content->data->currency,
                            'ip_address' =>$verifyTrans->content->data->ip_address,
                            'metadata' =>$verifyTrans->content->data->metadata,
                            'log' =>$verifyTrans->content->data->log,
                            'fees' =>$verifyTrans->content->data->fees,
                            'plan' =>$verifyTrans->content->data->plan,
                            'authorization' => json_encode($verifyTrans->content->data->authorization)
                        ]
                    );

                    return response()->json(
                        [
                            'message' => $verifyTrans->content->message,
                            'data' => null,
                            'status' => '400'],
                        400);

                }elseif ($verifyTrans->content->data->status == 'success') {

                    PayStackTransaction::create(
                        [
                            'status' =>TransactionStatus::COMPLETE(),
                            'description' => 'wallet_credit',
                            'amount' => $request->input('amount'),
                            'gateway_response' =>$verifyTrans->content->data->gateway_response,
                            'message' =>$verifyTrans->content->data->message,
                            'channel' =>$verifyTrans->content->data->channel,
                            'reference' =>$verifyTrans->content->data->reference,
                            'paid_at' =>$verifyTrans->content->data->transaction_date,
                            'created_at_ps' =>$verifyTrans->content->data->transaction_date,
                            'currency' =>$verifyTrans->content->data->currency,
                            'ip_address' =>$verifyTrans->content->data->ip_address,
                            'metadata' =>$verifyTrans->content->data->metadata,
                            'log' =>$verifyTrans->content->data->log,
                            'fees' =>$verifyTrans->content->data->fees,
                            'plan' =>$verifyTrans->content->data->plan,
                            'authorization' => json_encode($verifyTrans->content->data->authorization)
                        ]
                    );


                    //Credit User
                    $user = \App\User::find($request->user()->id)->first();
                    $user->deposit( $request->input('amount'), ['narration' => 'Wallet Credit','payment_ref'=>$verifyTrans->content->data->reference]);
                    Mail::to($request->user()->email)->send(new Wallet(
                            $request->user()->firstname,
                            "You have successfully recharged your wallet with ".$request->input('amount') . " Naira",
                            "Wallet Top-up"
                        )
                    );
                    //Return response
                    return response()->json(
                        [
                            'message' => 'Credit successful',
                            'data' => ['balance' => $user->balance],
                            'status' => '200'],
                        200);


//                    Mail::to($trans->user_email)->send(new AmazonSes($ref,$trans,$cart_with_variants));


                }
            }

            return response()->json(
                [
                    'message' => 'Failed to complete payment',
                    'data' => null,
                    'status' => 'fail'],
                400);
        }


        return response()->json(
            [
                'message' => 'Invalid Authorization',
                'data' => null,
                'status' => 'fail'],
            400);


    }

    public function debit(Request $request){

        $valid = Validator::make($request->all(), [
            'amount' => 'required|numeric'
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }


        //Debit User
        $user = \App\User::find($request->user()->id)->first();
        $user->withdraw( $request->input('amount'));

        //Build response
        $resource = new Fractal\Resource\Item($user,new UserTransformer);
        $response = $this->apiResponse()->createData($resource)->toArray();

        //Return response
        return response()->json(
            [
                'message' => 'Debit successful',
                'data' => $response['data'],
                'status' => '200'],
            200);
    }

    public function transfer(Request $request){

        $valid = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'wallet_id' => 'required',
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }


        //Check if User Wallet exists
        $wallet_exists = \App\User::where('email',$request->input('wallet_id'))->orWhere('phone',$request->input('wallet_id'))->exists();
        if(!$wallet_exists){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'Invalid Wallet ID'
            ], 400);
        }


        if($request->user()->email == $request->input('wallet_id')){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'Sorry! Transfer to same wallet is not allowed'
            ], 400);
        }

        if($request->user()->phone == $request->input('wallet_id')){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'Sorry! Transfer to same wallet is not allowed'
            ], 400);
        }


        //Debit User
            $sender = \App\User::find($request->user()->id)->first();
            if ($sender) {
            if ($sender->balance >= $request->input('amount') ) {
                $sender->withdraw($request->input('amount'),['narration' => 'Wallet Transfer Debit']);
            }else{
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'Sorry! Your account balance is insufficient for this transaction'
                ], 400);
            }
            }


        //Get Wallet
        $receiver = \App\User::where('email',$request->input('wallet_id'))->orWhere('phone',$request->input('wallet_id'))->first();

        //Credit wallet
        $receiver->deposit( $request->input('amount'),['narration' => 'Wallet Transfer Credit']);

        //Build response
        $resource = new Fractal\Resource\Item($receiver,new UserTransformer);
        $response = $this->apiResponse()->createData($resource)->toArray();

        //Send Notification
        if($sender->device->active == 1) {
            $title = "Wallet Credit";
            $title2 = "Wallet Debit";
            $body = "You have received a top-up of " . $request->input('amount') . " Naira from " . $sender->firstname;
            $body2 ="You transferred ".$request->input('amount') . " Naira to " . $receiver->firstname;

            $receiver_payload = [
                "to" => $receiver->device->fcm_token,
                "notification" => [
                    "body" => $body,
                    "title" => $title
                ]
            ];

            $sender_payload = [
                "to" => $sender->device->fcm_token,
                "notification" => [
                    "body" => $body2,
                    "title" => $title2
                ]
            ];

//            Queue::push(new SendNotification($payload));
//
//            Queue::push(new SendNotification($payload2));
        $this->notificationProxy->sendFcmNotification($receiver_payload);
        $this->notificationProxy->sendFcmNotification($sender_payload);

            //Record Notification
            \App\Notification::create([
                'user_id' => $sender->id,
                'title' => $title2,
                'message' => $body2,
            ]);

            //Record Notification
            \App\Notification::create([
                'user_id' => $receiver->id,
                'title' => $title,
                'message' => $body,
            ]);
        }
        Mail::to($receiver->email)->send(new Wallet(
                $receiver->firstname,
                "You have received a wallet Top-up of ".$request->input('amount') . " Naira from ".$request->user()->firstname,
                "Wallet Top-up"
            )
        );
        //Return response
        return response()->json(
            [
                'message' => 'Transfer successful',
                'data' => $response['data'],
                'status' => '200'],
            200);
    }

    public function Cards(Request $request){
        $cards = CardTokens::where('user_id', '=', $request->user()->id)->get();

        return response()->json(
            [
                'message' => 'Successful',
                'data' => $cards,
                'status' => '200'],
            200);
    }

    public function RemoveCard(Request $request){
        CardTokens::where('user_id', '=', $request->user()->id)->where('authorization_code', '=', $request->authorization_code)->delete();
        $cards = CardTokens::where('user_id', '=', $request->user()->id)->get();
        return response()->json(
            [
                'message' => 'Card Removed',
                'data' => $cards,
                'status' => '200'],
            200);
    }



}
