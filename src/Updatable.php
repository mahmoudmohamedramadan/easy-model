<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\Update\HasModel as UpdatableModel;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait Updatable
{
    use UpdatableModel;

    /**
     * Get an updatable eloquent query builder.
     *
     * @param  string|null  $relationship
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getUpdatableEloquentBuilder(string $relationship = null)
    {
        if (empty($this->getUpdatableModel())) {
            throw new InvalidModel('You must set the updatable model first.');
        }

        return empty($relationship) ?
            $this->getUpdatableModel()->newQuery() :
            $this->getUpdatableModel()->{$relationship}()->getQuery();
    }

    /**
     * Update records in the database.
     *
     * @param  array  $values
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performUpdateQuery(array $values)
    {
        return $this->performOnSearchOrUpdateQuery('update', $values);
    }

    /**
     * Delete records from the database.
     *
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performDeleteQuery()
    {
        return $this->performOnSearchOrUpdateQuery('delete');
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function upsertModel(array $attributes, array $values = [], $model = null)
    {
        if (!empty($model)) {
            $this->setUpdatableModel($model);
        }

        $this->updatableModel = $this->getUpdatableEloquentBuilder()->updateOrCreate($attributes, $values);

        return $this;
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  string  $relationship
     * @param  array  $attributes
     * @param  array  $values
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function upsertRelationship(string $relationship, array $attributes, array $values = [])
    {
        $this->getUpdatableEloquentBuilder($relationship)->updateOrCreate($attributes, $values);

        return $this;
    }

    /**
     * Perform the given action method on the "Search" or "Update" query.
     *
     * @param  string  $method
     * @param  mixed|null  $paramters
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function performOnSearchOrUpdateQuery(string $method, mixed $parameters = null)
    {
        if (method_exists($this, 'getSearchableEloquentBuilder')) {
            $this->getSearchableEloquentBuilder()->{$method}($parameters);

            return $this;
        }

        $this->getUpdatableEloquentBuilder()->{$method}($parameters);

        return $this;
    }

    /**
     * Retrieve the result of the query.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function fetch()
    {
        return $this->getUpdatableModel();
    }
}
