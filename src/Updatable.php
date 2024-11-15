<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\Update\HasModel as UpdatableModel;

trait Updatable
{
    use UpdatableModel;

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [], $model = null)
    {
        if (!empty($model)) {
            $this->setUpdatableModel($model);
        }

        return $this->getUpdatableModel()->updateOrCreate($attributes, $values);
    }
}
