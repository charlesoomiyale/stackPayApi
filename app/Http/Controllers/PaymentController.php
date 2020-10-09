<?php

namespace App\Http\Controllers;


use App\PayStackTransaction;
use Illuminate\Http\Request;
use App\Http\Proxy\PayStackProxy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\JWTAuth;

class PaymentController extends Controller
{

    private $payStackProxy;
    private $curlService;

    public function __construct(JWTAuth $jwt,PayStackProxy $PayStackProxy)
    {
        $this->payStackProxy = $PayStackProxy;
        $this->curlService = new \Ixudra\Curl\CurlService();
        $this->middleware('auth:api');
        $this->jwt = $jwt;
    }

    public function startTransaction(Request $request){

        $valid = Validator::make($request->all(), [
            'amount' => 'required|integer',
        ]);

        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }
//
        $ref = Str::uuid()->toString();
        $initPayStack = $this->payStackProxy->initializeTransaction($request->user()->email, $request->amount , $ref);
        if($initPayStack){
            if($initPayStack->content->status) {
                $trans = new PayStackTransaction();
                $trans->name = $request->user()->firstname . ' ' .$request->user()->lastname;
                $trans->description = $request->description;
                $trans->reference = $ref;
                $trans->amount = $request->amount;
                $trans->access_code = $initPayStack->content->data->access_code;
                $trans->user_id = $request->user()->id;
                $trans->save();

                return response()->json(
                    [
                        'message' => $initPayStack->content->message,
                        'data' => $initPayStack->content->data,
                        'status' => '200'],
                    200);
            }
                } else {
            return response()->json(
            [
            'message' => 'Failed to start transaction',
            'data' => null,
            'status' => '400'],
            400);
            }


    }


    public function records(Request $request){

        $purchases = \App\PurchaseTransaction::where('user_id', '=', $request->user()->id)->latest()->get();

        $wallet_transactions = $request->user()->wallet->transactions->sortByDesc('created_at')->flatten();

        return response()->json(
            [
                'message' => 'Transactions found',
                'data' => [
                    'statement' => is_countable($wallet_transactions) ? $wallet_transactions : null,
                    'purchases' => $purchases
                ],
                'status' => '200',
                'wallet'=>$request->user()->wallet->transactions
            ],
            200);
    }




}
