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
     * Get an updatable query builder.
     *
     * @param  string|null  $relationship
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getUpdatableQueryBuilder(string $relationship = null)
    {
        return $this->getUpdatableEloquentBuilder($relationship)->toBase();
    }

    /**
     * Update records in the database.
     *
     * @param  array  $values
     * @param  array  $incrementEach
     * @param  array  $decrementEach
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performUpdateQuery(array $values, array $incrementEach = [], array $decrementEach = [])
    {
        $this->getSearchOrUpdateQuery()->update($values);

        if (!empty($incrementEach)) {
            $this->getSearchOrUpdateQuery(true)->incrementEach($incrementEach);
        }

        if (!empty($decrementEach)) {
            $this->getSearchOrUpdateQuery(true)->decrementEach($decrementEach);
        }

        return $this;
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
        return $this->getSearchOrUpdateQuery()->delete();
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  string|null  $relationship
     * @param  array  $incrementEach
     * @param  array  $decrementEach
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function updateOrCreateModel(
        array $attributes,
        array $values = [],
        $model = null,
        array $incrementEach = [],
        array $decrementEach = []
    ) {
        if (!empty($model)) {
            $this->setUpdatableModel($model);
        }

        $this->updatableModel = $this->getUpdatableEloquentBuilder()->updateOrCreate($attributes, $values);

        if (!empty($incrementEach)) {
            $this->getUpdatableQueryBuilder()->incrementEach($incrementEach);
        }

        if (!empty($decrementEach)) {
            $this->getUpdatableQueryBuilder()->decrementEach($decrementEach);
        }

        return $this;
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  string  $relationship
     * @param  array  $attributes
     * @param  array  $values
     * @param  \Illuminate\Database\Eloquent\Model|string|null  $model
     * @param  array  $incrementEach
     * @param  array  $decrementEach
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function updateOrCreateRelationship(
        string $relationship,
        array $attributes,
        array $values = [],
        $model = null,
        array $incrementEach = [],
        array $decrementEach = []
    ) {
        if (!empty($model)) {
            $this->setUpdatableModel($model);
        }

        $this->getUpdatableEloquentBuilder($relationship)->updateOrCreate($attributes, $values);

        if (!empty($incrementEach)) {
            $this->getUpdatableQueryBuilder($relationship)->incrementEach($incrementEach);
        }

        if (!empty($decrementEach)) {
            $this->getUpdatableQueryBuilder($relationship)->decrementEach($decrementEach);
        }

        return $this;
    }

    /**
     * Get the appropriate query builder based on the context (searchable or updatable) and the type of builder required.
     *
     * @param  bool  $isQueryBuilder
     * @return \\Illuminate\Database\Eloquent\Builder|Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchOrUpdateQuery(bool $isQueryBuilder = false)
    {
        if ($isQueryBuilder) {
            return method_exists($this, 'getSearchableQueryBuilder')
                ? $this->getSearchableQueryBuilder()
                : $this->getUpdatableQueryBuilder();
        }

        return method_exists($this, 'getSearchableEloquentBuilder')
            ? $this->getSearchableEloquentBuilder()
            : $this->getUpdatableEloquentBuilder();
    }

    /**
     * Retrieve the result of the query.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function fetch()
    {
        return $this->getUpdatableModel()->refresh();
    }
}
