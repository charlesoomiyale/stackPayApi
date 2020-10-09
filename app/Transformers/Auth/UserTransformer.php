<?php

namespace App\Transformers\Auth;

use App\User as Model;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{

    public function transform(Model $model)
    {
        return [
            'fullname' => (string)$model->fullname,
            'firstname' => (string)$model->firstname,
            'lastname' => (string)$model->lastname,
            'email_verified_at' => (string)$model->email_verified_at,
            'is_active' => (integer)$model->is_active,
            'last_login_at' => $model->last_login_at,
            'login_count' => (integer)$model->login_count,
            'email' => (string)$model->email,
            'phone' => (string)$model->phone,
            'balance' =>  (string)$model->balance,
            'created_at' => (string)$model->created_at->toIso8601String()
        ];
    }

}
