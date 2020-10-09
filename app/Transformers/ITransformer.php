<?php

interface ITransformer {
    /**
     * @param Model $model
     * @return mixed
     */
    public function transform($model);
}