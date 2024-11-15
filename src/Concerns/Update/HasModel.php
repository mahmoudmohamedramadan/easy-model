<?php

namespace Ramadan\EasyModel\Concerns\Update;

use Illuminate\Database\Eloquent\Model;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait HasModel
{
    /**
     * The model to update.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $updatableModel;

    /**
     * Set the updatable model without chaining the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function setUpdatableModel($model)
    {
        if (!is_string($model) && !is_a($model, Model::class, true)) {
            throw new InvalidModel(sprintf(
                'The model must be string or instance of \Illuminate\Database\Eloquent\Model. Given [%s].',
                gettype($model)
            ));
        }

        $this->updatableModel = $model;
    }

    /**
     * Get the current updatable model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getUpdatableModel()
    {
        if (is_string($this->updatableModel)) {
            return new $this->updatableModel;
        }

        return $this->updatableModel;
    }
}
