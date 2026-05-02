<?php

namespace Ramadan\EasyModel;

use Illuminate\Database\Eloquent\Model;

class EasyModel
{
    use Searchable;

    /**
     * Create a new EasyModel instance bound to the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return static
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public static function for(Model|string $model)
    {
        $instance = new static();

        $instance->setSearchableModel($model);
        $instance->setUpdatableModel($model);

        return $instance;
    }
}
