<?php

namespace Ramadan\EasyModel\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasModel
{
    /**
     * The model to search in.
     *
     * @var string
     */
    protected $model;

    /**
     * Set the model without chaining the query.
     *
     * @param  string  $model
     * @return void
     */
    public function setModel(string $model)
    {
        $this->model = $model;
    }

    /**
     * Get the current model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the model then chain the query.
     *
     * @param  string  $model
     * @return $this
     */
    public function setChainableModel(string $model)
    {
        $this->setModel($model);

        return $this;
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
