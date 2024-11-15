<?php

namespace Ramadan\EasyModel\Concerns\Search;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait HasModel
{
    /**
     * The model to search in.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $searchableModel;

    /**
     * The relationship to search in.
     *
     * @var string
     */
    protected $searchableRelationship;

    /**
     * The resolved model or relationship instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelOrRelation;

    /**
     * Set the searchable model without chaining the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function setSearchableModel($model)
    {
        if (!is_string($model) && !is_a($model, Model::class, true)) {
            throw new InvalidModel(sprintf(
                'The model must be string or instance of \Illuminate\Database\Eloquent\Model. Given [%s].',
                gettype($model)
            ));
        }

        $this->searchableModel = $model;
    }

    /**
     * Get the current searchable model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getSearchableModel()
    {
        if (is_string($this->searchableModel)) {
            return new $this->searchableModel;
        }

        return $this->searchableModel;
    }

    /**
     * Resolve and return the model or relationship based on the given parameters.
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

        $relationship = $this->getSearchableRelationship();
        $model        = $this->getSearchableModel();

        return empty($relationship) ? $model : $model->{$relationship}()->getRelated();
    }

    /**
     * Set the searchable model then chain the query.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function setSearchableChainableModel($model)
    {
        $this->setSearchableModel($model);

        return $this;
    }

    /**
     * Set the searchable model relationship.
     *
     * @param  string  $relationship
     * @return $this
     */
    public function setSearchableRelationship(string $relationship)
    {
        $this->searchableRelationship = $relationship;

        return $this;
    }

    /**
     * Get the current searchable model relationship.
     *
     * @return string
     */
    protected function getSearchableRelationship()
    {
        return $this->searchableRelationship;
    }

    /**
     * Resolve the model for the current context.
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function resolveModel()
    {
        // We will check if the model has been set using the "setModel" or "setChainableModel"
        // method as a manual setting is more of a priority otherwise it means the developer
        // uses the "Searchable" trait in the model itself.
        if (!empty($this->getSearchableModel())) {
            return;
        }

        if (is_a(self::class, Model::class, true)) {
            $this->setSearchableModel(self::class);
            return;
        }

        throw new InvalidModel('Cannot resolve the searchable model.');
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
