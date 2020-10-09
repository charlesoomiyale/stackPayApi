<?php

namespace App\Transformers;

use App\Bouquet as Model;
use League\Fractal\TransformerAbstract;

class BouquetsTransformer extends TransformerAbstract
{

    public function transform(Model $model)
    {
        return [
            'name' => (string)$model->name,
            'code' => (string)$model->code,
            'description' => (string)$model->description,
            'service_type' => (string)$model->service_type,
            'image' => (integer)$model->image,
            'availablePricingOptions' => json_decode($model->availablePricingOptions),
        ];
    }

}
