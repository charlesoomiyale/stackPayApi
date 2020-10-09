<?php

use Illuminate\Support\Facades\Cache;

//$router->get('/demo', function () {
//    return new App\Mail\Receipt("Victor", "Data Top-up", "mtn","09088223322", "9923jjj233232", "40000");
//});

$router->group(['prefix' => 'v1'], function () use ($router) {
//    $router->get('/testn', 'ApiController@sendNotification');

    $router->group(['prefix' => 'auth'], function () use ($router) {

    $router->post('login', 'AuthController@login');
    $router->post('register', 'AuthController@register');
    $router->get('refresh', 'AuthController@refresh');
    $router->get('profile', 'AuthController@me');
    $router->post('profile/update', 'AuthController@update');
    $router->post('password/forgot', 'AuthController@forgotPassword');
    $router->post('password/reset', 'AuthController@resetPassword');


    });


    $router->group(['prefix' => 'data'], function () use ($router) {
        $router->get('providers', 'DataController@getProviders');
        $router->get('providers/update', 'DataController@updateProviders');
        $router->get('provider/{name}', 'DataController@getProvider');
        $router->get('provider/{name}/update', 'DataController@updateBundles');
        $router->post('purchase', 'DataController@purchase');
    });

    $router->group(['prefix' => 'airtime'], function () use ($router) {
        $router->get('providers', 'AirtimeController@getProviders');
        $router->get('providers/update', 'AirtimeController@updateProviders');
        $router->post('purchase', 'AirtimeController@purchase');
    });

    $router->group(['prefix' => 'tv'], function () use ($router) {
        $router->get('providers', 'CableTvController@getProviders');
        $router->get('providers/update', 'CableTvController@updateProviders');
        $router->post('addons', 'CableTvController@getBouquetAddons');
        $router->get('multichoice/list/{service_type}', 'CableTvController@getBouquets');
        $router->get('multichoice/list/{service_type}/update', 'CableTvController@updateBouquets');
        $router->post('purchase', 'CableTvController@purchase');
    });

    $router->group(['prefix' => 'electricity'], function () use ($router) {
        $router->get('billers', 'ElectricityController@getBillers');
        $router->get('billers/update', 'ElectricityController@updateProviders');
        $router->post('customer/verify', 'ElectricityController@verifyCustomer');
        $router->post('purchase', 'ElectricityController@purchase');
    });

    $router->group(['prefix' => 'wallet'], function () use ($router) {
        $router->post('credit', 'WalletController@credit');
        $router->post('credit/token', 'WalletController@creditWithToken');
        $router->post('debit', 'WalletController@debit');
        $router->post('transfer', 'WalletController@transfer');
        $router->get('cards', 'WalletController@Cards');
        $router->post('card/remove', 'WalletController@RemoveCard');
    });


    $router->group(['prefix' => 'paystack'], function () use ($router) {
        $router->post('transaction/start', 'PaymentController@startTransaction');
    });

    $router->group(['prefix' => 'records'], function () use ($router) {
        $router->get('list', 'PaymentController@records');
    });

    $router->group(['prefix' => 'device'], function () use ($router) {
        $router->post('link', 'AuthController@linkDevice');
    });

    $router->group(['prefix' => 'notification'], function () use ($router) {
        $router->get('list', 'AuthController@notifications');
        $router->get('read/all', 'AuthController@readNotification');
        $router->get('read/{id}', 'AuthController@readNotification');
    });

    $router->group(['prefix' => 'cache'], function () use ($router) {
        $router->get('clear', function (){
            Cache::flush();
        });
    });

    $router->group(['prefix' => 'common'], function () use ($router) {
        $router->post('account_query', 'CommonController@nameFinder');
    });

    $router->group(['prefix' => 'billers'], function () use ($router) {
        $router->get('all/categories', 'BillersController@allCategories');
        $router->get('{service_type}', 'BillersController@byCategory');
    });
});
