<?php

namespace Ramadan\EasyModel;

use Illuminate\Support\Facades\DB;
use Ramadan\EasyModel\Concerns\Update\HasModel as UpdatableModel;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait Updatable
{
    use UpdatableModel;

    /**
     * The changes that have been made to the model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $appliedChanges;

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
    protected function getUpdatableEloquentBuilder(string $relationship = null)
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
    protected function getUpdatableQueryBuilder(string $relationship = null)
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
        return $this->getSearchOrUpdateQuery(isQueryBuilder: $usingQueryBuilder)->update($values);
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
        return $this->getSearchOrUpdateQuery(isQueryBuilder: $usingQueryBuilder)->delete();
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
        // If a model has been created or updated, it takes precedence. In such cases,
        // we will increment the values of its columns.
        if (!empty($this->appliedChanges)) {
            foreach ($attributes as $column => $value) {
                $this->appliedChanges->{$column} += $value;
            }

            $this->appliedChanges->save();

            return $this;
        }

        $this->setSearchOrUpdateQuery($usingQueryBuilder);

        /**
         * @see https://php.net/manual/en/closure.call.php
         */
        $extra = !$usingQueryBuilder ?
            (fn($args) => $this->addUpdatedAtColumn($args))->call($this->getSearchOrUpdateQuery(), []) :
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
        // If a model has been created or updated, it takes precedence. In such cases,
        // we will decrement the values of its columns.
        if (!empty($this->appliedChanges)) {
            foreach ($attributes as $column => $value) {
                $this->appliedChanges->{$column} -= $value;
            }

            $this->appliedChanges->save();

            return $this;
        }

        $this->setSearchOrUpdateQuery($usingQueryBuilder);

        /**
         * @see https://php.net/manual/en/closure.call.php
         */
        $extra = !$usingQueryBuilder ?
            (fn($args) => $this->addUpdatedAtColumn($args))->call($this->getSearchOrUpdateQuery(), []) :
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
        // If a model has been created or updated, it takes precedence. In such cases,
        // we will zero out the values of its columns.
        if (!empty($this->appliedChanges)) {
            $this->appliedChanges->update(array_fill_keys($attributes, 0));

            return $this;
        }

        $this->setSearchOrUpdateQuery($usingQueryBuilder);

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
        // If a model has been created or updated, it takes precedence. In such cases,
        // we will toggle the values of its columns.
        if (!empty($this->appliedChanges)) {
            $columns = $this->appliedChanges->only($attributes);

            $toggle = array_map(fn($value) => !$value, $columns);

            $this->appliedChanges->update($toggle);

            return $this;
        }

        $this->setSearchOrUpdateQuery($usingQueryBuilder);

        $this->searchOrUpdateQuery->update(
            array_map(fn($attribute) => [$attribute => DB::raw("NOT $attribute")], $attributes)[0]
        );

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
    protected function getSearchOrUpdateQuery(string $relationship = null, bool $isQueryBuilder = false)
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
     * Set the builder for the current context "Searchable" or "Updatable".
     *
     * @param  bool  $usingQueryBuilder
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function setSearchOrUpdateQuery(bool $usingQueryBuilder = false)
    {
        if (empty($this->searchOrUpdateQuery)) {
            $this->searchOrUpdateQuery = $this->getSearchOrUpdateQuery(isQueryBuilder: $usingQueryBuilder);
        }
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
        return $this->getSearchOrUpdateQuery(isQueryBuilder: $isQueryBuilder);
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
        return $this->appliedChanges?->refresh() ?? $this->fetchBuilder($usingQueryBuilder)->get();
    }
}
