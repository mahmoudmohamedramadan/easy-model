<?php

namespace Ramadan\EasyModel\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
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
     * The relationship to search in.
     *
     * @var string
     */
    protected $relationship;

    /**
     * The current model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelOrRelation;

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
     * Get the current model instance.
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
     * Resolve the current model.
     *
     * @param  string|null  $givenRelationship
     * @param  \Illuminate\Database\Eloquent\Model|null  $givenModel
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function resolveModelOrRelation($givenRelationship = null, $givenModel = null)
    {
        if (!empty($this->modelOrRelation)) {
            return $this->modelOrRelation;
        }

        if (!empty($givenRelationship) && !empty($givenModel)) {
            return $givenModel->{$givenRelationship}()->getRelated();
        }

        $relationship = $this->getRelationship();
        $model        = $this->getModel();

        return empty($relationship) ? $model : $model->{$relationship}()->getRelated();
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
     * Resolve the model for the current context.
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function resolveModel()
    {
        // We will check if the model has been set using the "setModel" or "setChainableModel"
        // method as a manual setting is more of a priority otherwise it means the developer
        // uses the "Searchable" trait in the model itself.
        if (!empty($this->getModel())) {
            return;
        }

        if (is_a(self::class, Model::class, true)) {
            $this->setModel(self::class);
            return;
        }

        throw new InvalidSearchableModel('Cannot resolve the searchable model.');
    }

    /**
     * Apply the scopes to the query.
     *
     * @param  array  $scopes
     * @return $this
     */
    public function usingScopes(array $scopes)
    {
        $localScopes = [];

        foreach ($scopes as $scope => $parameters) {
            if (is_a($parameters, Scope::class, true)) {
                $identifier = is_string($parameters) ? $parameters : get_class($parameters);
                $scope      = is_string($parameters) ? new $parameters : $parameters;
                $this->eloquentBuilder = $this->getEloquentBuilder()->withGlobalScope($identifier, $scope);
            } else {
                $localScopes[$scope] = $parameters;
            }
        }

        if (!empty($localScopes)) {
            $this->eloquentBuilder = $this->getEloquentBuilder()->scopes($localScopes);
        }

        return $this;
    }
}
