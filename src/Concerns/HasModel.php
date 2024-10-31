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
     * The current model or the related model based on the relationship value.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelOrRelation;

    /**
     * The relationship to search in.
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
     * Get the current model or the related model based on the relationship value.
     *
     * @param  string|null  $relationship
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function resolveModelOrRelation($relationship = null, $model = null)
    {
        if (!empty($this->modelOrRelation)) {
            return $this->modelOrRelation;
        }

        if (!empty($relationship) && !empty($model)) {
            return empty($relationship) ? $model : $model->{$relationship}()->getRelated();
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

        foreach ($scopes as $scope) {
            if (is_a($scope, Scope::class, true)) {
                $identifier            = is_string($scope) ? $scope : get_class($scope);
                $scope                 = is_string($scope) ? new $scope : $scope;
                $this->getEloquentBuilder()->withGlobalScope($identifier, $scope);
            } else {
                $localScopes[] = $scope;
            }
        }

        if (!empty($localScopes)) {
            $this->getEloquentBuilder()->scopes($localScopes);
        }

        return $this;
    }
}
