<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait AuthValidation
{

    public function validateRegister(array $payload){
        try {
           return  Validator::make($payload, [
               'email' => 'required|email|max:255|unique:users|bail',
               'firstname' => 'required|bail',
               'phone' => 'required|bail',
               'password' => 'required|bail',
           ]);

        } catch (ValidationException $e) {
            return $e->getMessage();
        }
    }


}
