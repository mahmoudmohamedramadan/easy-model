<?php

namespace Ramadan\EasyModel;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Ramadan\EasyModel\Concerns\Update\HasModel as UpdatableModel;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait Updatable
{
    use UpdatableModel;

    /**
     * The model that has been updated or is about to be updated.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $modelForUpdate;

    /**
     * The search or update builder.
     *
     * @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected $searchOrUpdateQuery;

    /**
     * Get an updatable eloquent builder.
     *
     * @param  string|null  $relationship
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getUpdatableEloquentBuilder(?string $relationship = null)
    {
        if (empty($this->getUpdatableModel())) {
            throw new InvalidModel("You must set the updatable model first.");
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
    protected function getUpdatableQueryBuilder(?string $relationship = null)
    {
        return $this->getUpdatableEloquentBuilder($relationship)->getQuery();
    }

    /**
     * Update records in the database.
     *
     * @param  array  $values
     * @param  bool  $usingQueryBuilder
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performUpdateQuery(array $values, bool $usingQueryBuilder = false)
    {
        return $this->getSearchOrUpdateBuilder(isQueryBuilder: $usingQueryBuilder)->update($values);
    }

    /**
     * Delete records from the database.
     *
     * @param  bool  $usingQueryBuilder  Whether to use the query builder to perform the delete.
     *                                   If true, the delete will bypass any soft deletes and
     *                                   permanently delete the records.
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performDeleteQuery(bool $usingQueryBuilder = false)
    {
        return $this->getSearchOrUpdateBuilder(isQueryBuilder: $usingQueryBuilder)->delete();
    }

    /**
     * Increment the given column's values by the given amounts.
     *
     * @param  array  $attributes
     * @param  bool  $usingQueryBuilder
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function incrementEach(array $attributes, bool $usingQueryBuilder = false)
    {
        $this->prepareUpdateQuery($usingQueryBuilder);

        // If a model has been created or updated, it takes precedence. In such cases,
        // we will increment the values of its columns.
        if (!empty($this->modelForUpdate)) {
            foreach ($attributes as $column => $value) {
                $this->modelForUpdate->{$column} += $value;
            }

            $this->modelForUpdate->save();

            return $this;
        }

        /**
         * @see https://php.net/manual/en/closure.call.php
         */
        $extra = !$usingQueryBuilder ?
            (fn($args) => $this->addUpdatedAtColumn($args))->call($this->getSearchOrUpdateBuilder(), []) :
            [];

        $this->searchOrUpdateQuery->incrementEach($attributes, $extra);

        return $this;
    }

    /**
     * Decrement the given column's values by the given amounts.
     *
     * @param  array  $attributes
     * @param  bool  $usingQueryBuilder
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function decrementEach(array $attributes, bool $usingQueryBuilder = false)
    {
        $this->prepareUpdateQuery($usingQueryBuilder);

        // If a model has been created or updated, it takes precedence. In such cases,
        // we will decrement the values of its columns.
        if (!empty($this->modelForUpdate)) {
            foreach ($attributes as $column => $value) {
                $this->modelForUpdate->{$column} -= $value;
            }

            $this->modelForUpdate->save();

            return $this;
        }

        /**
         * @see https://php.net/manual/en/closure.call.php
         */
        $extra = !$usingQueryBuilder ?
            (fn($args) => $this->addUpdatedAtColumn($args))->call($this->getSearchOrUpdateBuilder(), []) :
            [];

        $this->searchOrUpdateQuery->decrementEach($attributes, $extra);

        return $this;
    }

    /**
     * Reset the given column's values to zero.
     *
     * @param  array  $attributes
     * @param  bool  $usingQueryBuilder
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function zeroOutColumns(array $attributes, bool $usingQueryBuilder = false)
    {
        $this->prepareUpdateQuery($usingQueryBuilder);

        // If a model has been created or updated, it takes precedence. In such cases,
        // we will zero out the values of its columns.
        if (!empty($this->modelForUpdate)) {
            $this->modelForUpdate->update(array_fill_keys($attributes, 0));

            return $this;
        }

        $this->searchOrUpdateQuery->update(array_fill_keys($attributes, 0));

        return $this;
    }

    /**
     * Toggle the given column's values.
     *
     * @param  array  $attributes
     * @param  bool  $usingQueryBuilder
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function toggleColumns(array $attributes, bool $usingQueryBuilder = false)
    {
        $this->prepareUpdateQuery($usingQueryBuilder);

        // If a model has been created or updated, it takes precedence. In such cases,
        // we will toggle the values of its columns.
        if (!empty($this->modelForUpdate)) {
            $columns = $this->modelForUpdate->only($attributes);

            $toggle = array_map(fn($value) => !$value, $columns);

            $this->modelForUpdate->update($toggle);

            return $this;
        }

        $columns = collect($attributes)
            ->mapWithKeys(fn($attribute) => [$attribute => DB::raw("NOT $attribute")])
            ->toArray();

        $this->searchOrUpdateQuery->update($columns);

        return $this;
    }

    /**
     * Get an appropriate builder based on the context "Searchable" or "Updatable".
     *
     * @param  string|null  $relationship
     * @param  bool  $isQueryBuilder
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchOrUpdateBuilder(?string $relationship = null, bool $isQueryBuilder = false)
    {
        // If the "setRelationship" method exists, it means the request is coming
        // from the "Searchable" context since the "Updatable" trait is used there.
        if (!empty($relationship) && method_exists($this, 'setRelationship')) {
            $this->setRelationship($relationship);
        }

        if ($isQueryBuilder) {
            return method_exists($this, 'getSearchableQueryBuilder') ?
                $this->getSearchableQueryBuilder() :
                $this->getUpdatableQueryBuilder($relationship);
        }

        return method_exists($this, 'getSearchableEloquentBuilder') ?
            $this->getSearchableEloquentBuilder() :
            $this->getUpdatableEloquentBuilder($relationship);
    }

    /**
     * Prepare the update query.
     *
     * @param  bool  $usingQueryBuilder
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function prepareUpdateQuery(bool $usingQueryBuilder = false)
    {
        $model = $this->getUpdatableModel();

        // If the developer has set a model for update, it takes precedence.
        if (!empty($model) && $model->exists) {
            $this->modelForUpdate = $model;
        } elseif (empty($this->searchOrUpdateQuery)) {
            $this->searchOrUpdateQuery = $this->getSearchOrUpdateBuilder(isQueryBuilder: $usingQueryBuilder);
        }
    }

    /**
     * Set the given query according to its type.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    public function setUpdatableQuery(QueryBuilder|EloquentBuilder|null $query = null)
    {
        if ($query instanceof QueryBuilder) {
            $this->searchOrUpdateQuery = $query;
        } elseif ($query instanceof EloquentBuilder) {
            $this->searchOrUpdateQuery = $query->getQuery();
        }

        return $this;
    }

    /**
     * Fetch the builder instance.
     *
     * @param  bool  $isQueryBuilder
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function fetchBuilder(bool $isQueryBuilder = false)
    {
        return $this->getSearchOrUpdateBuilder(isQueryBuilder: $isQueryBuilder);
    }

    /**
     * Fetch the result.
     *
     * @param  bool  $usingQueryBuilder
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function fetch(bool $usingQueryBuilder = false)
    {
        return $this->modelForUpdate?->refresh() ?? $this->fetchBuilder($usingQueryBuilder)->get();
    }
}
