<?php
namespace App\Http\Proxy;

interface IBaseProxy
{
    function sendPost($url, $header, $payload);
    function sendGet($url,$header);
}
