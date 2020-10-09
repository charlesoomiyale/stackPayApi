<?php


namespace App\Http\Controllers;

use App\Jobs\SendNotification;
use App\Jobs\SendOnboardEmail;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use League\Fractal\Manager;
use Kreait\Firebase\Messaging;
use Illuminate\Support\Carbon;

class ApiController extends Controller
{


    public function __construct()
    {
//        $this->middleware('auth:api');
    }

    public function apiResponse(){
        return new Manager();
    }

    public function sendNotification(Request $request){
        try {

            Queue::push(new SendOnboardEmail((object)['email'=>'testaccount@mailinator.com']));
            return response()->json(
                [
                    'message' => "Sent",
                    'data'=>[],
                    'status' => '200'],
                200);
        }catch (\Exception $e){
            return response()->json(
                [
                    'message' => $e->getMessage(),
                    'data' => [],
                    'status' => '400'],
                400);
        }

    }

    public function handleResponse($data){
        if(isset($data)){
            if($data){
                if($data->status == 1){
                    return response()->json(
                        [
                            'message' => $data->message,
                            'data' => $data->data,
                            'status' => '200'],
                        200);
                }else if($data->status == 0){
                    return response()->json(
                        [
                            'message' => $data->message,
                            'data' => [],
                            'status' => '400'],
                        200);
                }
            }
        }else{
            return response()->json(
                [
                    'message' => 'Failed to retrive data. Try again.',
                    'data' => [],
                    'status' => '400'],
                400);
        }
    }
}
