<?php

namespace Ramadan\EasyModel\Concerns;

use Illuminate\Database\Eloquent\Model;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;

trait HasModel
{
    /**
     * The model to search in.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $model;

    /**
     * The model to search in.
     *
     * @var string
     */
    protected $relationship;

    /**
     * Set the model without chaining the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function setModel($model)
    {
        if (!is_string($model) && !is_a($model, Model::class, true)) {
            throw new InvalidSearchableModel(sprintf(
                'The model must be string or instance of \Illuminate\Database\Eloquent\Model. Given [%s].',
                gettype($model)
            ));
        }

        $this->model = $model;
    }

    /**
     * Get the current model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        if (is_string($this->model)) {
            return new $this->model;
        }

        return $this->model;
    }

    /**
     * Set the model then chain the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function setChainableModel($model)
    {
        $this->setModel($model);

        return $this;
    }

    /**
     * Set the model relationship.
     *
     * @param  string  $relationship
     * @return $this
     */
    public function setRelationship(string $relationship)
    {
        $this->relationship = $relationship;

        return $this;
    }

    /**
     * Get the current model relationship.
     *
     * @return string
     */
    protected function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * Try to guess the model if not provided.
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function guessModel()
    {
        // The trait may be used in the model that you need to search in so, we will
        // set the model value if not provided.
        if (!empty($this->getModel())) {
            return;
        }

        if (is_a(self::class, Model::class, true)) {
            $this->setModel(new self);
        }
    }
}
