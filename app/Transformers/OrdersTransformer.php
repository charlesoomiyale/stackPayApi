<?php

namespace App\Transformers;

use App\Order as Model;
use League\Fractal\TransformerAbstract;

class OrdersTransformer extends TransformerAbstract
{

    public function transform(Model $model)
    {
        return [
            'order_id' => (integer)$model->id,
            'unit_cost' => (string)$model->sub_total_cost,
            'is_shipping' => (int)$model->is_shipping,
            'shipping_cost' => (string)$model->shipping_cost,
            'order_no' => (integer)$model->order_no,
            'success' => $model->success,
            'fail' => $model->signature,
            'created_at' => (string)$model->created_at->toIso8601String()
        ];
    }

}