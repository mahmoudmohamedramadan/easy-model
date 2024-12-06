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
     * Set the updatable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function setUpdatableModel($model)
    {
        if (!is_string($model) && !is_a($model, Model::class, true)) {
            throw new InvalidModel(sprintf(
                "The model must be string or instance of \Illuminate\Database\Eloquent\Model. Given [%s].",
                gettype($model)
            ));
        }

        $this->updatableModel = $model;

        return $this;
    }

    /**
     * Get the current updatable model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getUpdatableModel()
    {
        return is_string($this->updatableModel) ? new $this->updatableModel : $this->updatableModel;
    }
}
