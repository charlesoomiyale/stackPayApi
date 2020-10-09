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

class BillersController extends Controller
{

    private $BaxiProxy;

    public function __construct(BaxiProxy $baxiProxy)
    {
        $this->BaxiProxy = $baxiProxy;
    }


    public function allCategories()
    {
        $response = $this->BaxiProxy->getAllProvidersCategories();

        if(isset($response->content->data)) {
            return response()->json(
                [
                    'message' => 'Providers loaded',
                    'data' => $response->content->data,
                    'status' => '200'],
                200);
        }
    }

    public function byCategory(string $service_type)
    {
        $response = $this->BaxiProxy->getProviderByCategory(['service_type'=>$service_type]);

        if(isset($response->content->data)) {
            return response()->json(
                [
                    'message' => 'Provider loaded',
                    'data' => $response->content->data,
                    'status' => '200'],
                200);
        }
    }


}
