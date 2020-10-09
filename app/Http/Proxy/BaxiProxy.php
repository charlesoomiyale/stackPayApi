<?php

namespace App\Http\Proxy;


class BaxiProxy extends BaseProxy
{

    protected $header;
    public function __construct()
    {
        $this->header = ["x-api-key: ".env('BAXI_API_KEY')];
    }


    public function getDataProviders(){
        return $this->sendGet(env('BAXI_BASE_URL').'services/databundle/providers', $this->header);
    }

    public function getDataProvider($payload){

        return $this->sendPost(env('BAXI_BASE_URL').'services/databundle/bundles', $this->header, $payload);
    }

    public function purchaseData($payload){

        return $this->sendPost(env('BAXI_BASE_URL').'services/databundle/request', $this->header, $payload);
    }


    //Airtime
    public function getAirtimeProviders(){
        return $this->sendGet(env('BAXI_BASE_URL').'services/airtime/providers', $this->header);
    }

    public function purchaseAirtime($payload){

        return $this->sendPost(env('BAXI_BASE_URL').'services/airtime/request', $this->header, $payload);
    }

    //TV
    public function getTvProviders(){
        return $this->sendGet(env('BAXI_BASE_URL').'services/cabletv/providers', $this->header);
    }

    public function getTvProvider($payload){
        return $this->sendPost(env('BAXI_BASE_URL').'services/multichoice/list', $this->header, $payload);
    }

    public function getTvProviderAddons($payload){
        return $this->sendPost(env('BAXI_BASE_URL').'services/multichoice/addons', $this->header, $payload);
    }

    public function nameFinder($payload){
        return $this->sendPost(env('BAXI_BASE_URL').'services/namefinder/query', $this->header, $payload);
    }

    public function purchaseTv($payload){

        return $this->sendPost(env('BAXI_BASE_URL').'services/multichoice/request', $this->header, $payload);
    }


    //Electricity
    public function getPowerBilers(){
        return $this->sendGet(env('BAXI_BASE_URL').'services/electricity/billers', $this->header);
    }

    public function verifyCustomer($payload){
        return $this->sendPost(env('BAXI_BASE_URL').'services/electricity/verify', $this->header, $payload);
    }

    public function purchasePower($payload){
        return $this->sendPost(env('BAXI_BASE_URL').'services/electricity/request', $this->header, $payload);
    }


    //All Providers
    public function getAllProvidersCategories(){
        return $this->sendGet(env('BAXI_BASE_URL').'billers/category/all', $this->header);
    }

    //Single Provider
    public function getProviderByCategory($payload){
        return $this->sendPost(env('BAXI_BASE_URL').'billers/services/category', $this->header,$payload);
    }

}
