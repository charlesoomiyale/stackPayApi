<?php

namespace App\Transformers;

use App\Cart as Model;
use League\Fractal\TransformerAbstract;

class CartTransformer extends TransformerAbstract
{

    public function transform(Model $model)
    {
        return [
            'id' => (integer)$model->id,
            'name' => (string)$model->name,
            'delivery_type' => (string)$model->delivery_type,
            'delivery_location' => (string)$model->delivery_location,
            'pickup_location_name' => (string)$model->pickup_location_name,
            'qty' => (integer)$model->qty,
            'price' => $model->price,
            'signature' => $model->signature,
            'created_at' => (string)$model->created_at->toIso8601String()
        ];
    }

}