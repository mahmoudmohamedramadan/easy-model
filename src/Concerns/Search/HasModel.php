<?php

namespace Ramadan\EasyModel\Concerns\Search;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
    protected $relationship;

    /**
     * The resolved model or relationship instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelOrRelation;

    /**
     * Set the searchable model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|string  $model
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function setSearchableModel($model)
    {
        if (! is_a($model, Model::class, true)) {
            throw InvalidModel::notAModelClass($model);
        }

        $this->searchableModel = $model;

        return $this;
    }

    /**
     * Get the current searchable model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getSearchableModel()
    {
        return is_string($this->searchableModel) ? new $this->searchableModel : $this->searchableModel;
    }

    /**
     * Set the searchable relationship.
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
     * Get the current searchable relationship.
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function resolveModel()
    {
        // We will check if the model has been set using the "setSearchableModel" method
        // as a manual setting is more of a priority otherwise it means the developer uses
        // the "Searchable" trait in the model itself.
        if (! empty($this->getSearchableModel())) {
            return;
        }

        if (is_a(self::class, Model::class, true)) {
            $this->setSearchableModel(self::class);
            return;
        }

        throw InvalidModel::unresolvable();
    }

    /**
     * Apply the scopes to the query.
     *
     * @param  array  $scopes
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function usingScopes(array $scopes)
    {
        $localScopes = [];

        foreach ($scopes as $scope => $parameters) {
            if (is_a($parameters, Scope::class, true)) {
                $isClass = is_string($parameters);

                $identifier = $isClass ? $parameters : get_class($parameters);
                $scope      = $isClass ? new $parameters : $parameters;

                $this->eloquentBuilder = $this->getSearchableEloquentBuilder()->withGlobalScope($identifier, $scope);
            } else {
                $localScopes[$scope] = $parameters;
            }
        }

        if (! empty($localScopes)) {
            $this->eloquentBuilder = $this->getSearchableEloquentBuilder()->scopes($localScopes);
        }

        return $this;
    }

    /**
     * Ignore the global scopes or specific passed scopes for the current query.
     *
     * @param  array|null  $scopes
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function ignoreGlobalScopes(?array $scopes = null)
    {
        $this->eloquentBuilder = $this->getSearchableEloquentBuilder()->withoutGlobalScopes($scopes);

        return $this;
    }

    /**
     * Include soft-deleted records in the query results.
     *
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function includeSoftDeleted()
    {
        $model = $this->getSearchableModel();

        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            throw InvalidModel::softDeletesNotUsed(get_class($model));
        }

        $this->eloquentBuilder = $this->getSearchableEloquentBuilder()->withoutGlobalScope(SoftDeletingScope::class);

        return $this;
    }

    /**
     * Resolve and return the model or relationship based on the given parameters.
     *
     * @param  string|null  $givenRelationship
     * @param  \Illuminate\Database\Eloquent\Model|null  $givenModel
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function resolveModelOrRelation($givenRelationship = null, $givenModel = null)
    {
        if (! empty($this->modelOrRelation)) {
            return $this->modelOrRelation;
        }

        if (! empty($givenRelationship) && ! empty($givenModel)) {
            return $givenModel->{$givenRelationship}()->getRelated();
        }

        $model        = $this->getSearchableModel();
        $relationship = $this->getRelationship();

        return empty($relationship) ? $model : $model->{$relationship}()->getRelated();
    }
}
