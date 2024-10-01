<?php

namespace Ramadan\EasyModel\Concerns;

use Illuminate\Database\Eloquent\Model;

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
     */
    public function setModel(Model|string $model)
    {
        $this->model = $model;
    }

    /**
     * Get the current model.
     *
     * @return \Illuminate\Database\Eloquent\Model|string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the model then chain the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return $this
     */
    public function setChainableModel(Model|string $model)
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
     */
    protected function guessModel()
    {
        // This trait may be used in the Model that you need to search in so, we will
        // guess the model using the Model name in case it is not provided.
        if (!empty($this->getModel())) {
            return;
        }

        if (is_a(self::class, Model::class, true)) {
            $this->setModel(self::class);
        }
    }
}
