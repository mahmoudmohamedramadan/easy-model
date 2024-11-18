<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\Update\HasModel as UpdatableModel;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait Updatable
{
    use UpdatableModel;

    /**
     * The changes that have been made.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $appliedChanges;

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
     * @param  array  $incrementEach
     * @param  array  $decrementEach
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performUpdateQuery(array $values, array $incrementEach = [], array $decrementEach = [])
    {
        $affectedRecords = $this->getSearchOrUpdateQuery()->update($values);

        if (!empty($incrementEach)) {
            $this->getSearchOrUpdateQuery(isQueryBuilder: true)->incrementEach($incrementEach);
        }

        if (!empty($decrementEach)) {
            $this->getSearchOrUpdateQuery(isQueryBuilder: true)->decrementEach($decrementEach);
        }

        return $affectedRecords;
    }

    /**
     * Delete records from the database.
     *
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performDeleteQuery()
    {
        return $this->getSearchOrUpdateQuery()->delete();
    }

    /**
     * Create or update a record matching the attributes against the model, and fill it with values.
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

        $this->appliedChanges = $this->getSearchOrUpdateQuery()->updateOrCreate($attributes, $values);

        if (!empty($incrementEach)) {
            $this->getSearchOrUpdateQuery(isQueryBuilder: true)->incrementEach($incrementEach);
        }

        if (!empty($decrementEach)) {
            $this->getSearchOrUpdateQuery(isQueryBuilder: true)->decrementEach($decrementEach);
        }

        return $this;
    }

    /**
     * Create or update a record matching the attributes against the relationship, and fill it with values.
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

        $this->appliedChanges = $this->getSearchOrUpdateQuery($relationship)->updateOrCreate($attributes, $values);

        if (!empty($incrementEach)) {
            $this->getSearchOrUpdateQuery($relationship, true)->incrementEach($incrementEach);
        }

        if (!empty($decrementEach)) {
            $this->getSearchOrUpdateQuery($relationship, true)->decrementEach($decrementEach);
        }

        return $this;
    }

    /**
     * Get an appropriate builder based on the context ("Searchable" or "Updatable").
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
     * Fetch the changes result that have been applied to the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function fetch()
    {
        return $this->appliedChanges->refresh();
    }
}
