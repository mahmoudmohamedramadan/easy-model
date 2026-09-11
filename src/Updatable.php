<?php

namespace Ramadan\EasyModel;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
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
     * The underlying queryable instance.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    protected $searchOrUpdateQuery;

    /**
     * Whether mass writes should load models and persist them so observers / casts fire.
     *
     * @var bool
     */
    protected bool $usingModelEvents = false;

    /**
     * Get an updatable eloquent builder.
     *
     * @param  string|null  $relationship
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getUpdatableEloquentBuilder($relationship = null)
    {
        $model = $this->getUpdatableModel();

        if (empty($model)) {
            throw InvalidModel::updatableNotSet();
        }

        if ($model->exists) {
            return $model;
        }

        return empty($relationship) ? $model->newQuery() : $model->{$relationship}()->getQuery();
    }

    /**
     * Get an updatable query builder.
     *
     * @param  string|null  $relationship
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getUpdatableQueryBuilder($relationship = null)
    {
        return $this->getUpdatableEloquentBuilder($relationship)->getQuery();
    }

    /**
     * Persist mass writes through individual model instances so observers, casts, and
     * mutators run. Query-builder updates skip those by default.
     *
     * @param  bool  $using
     * @return $this
     */
    public function usingModelEvents(bool $using = true)
    {
        $this->usingModelEvents = $using;

        return $this;
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
        if ($this->usingModelEvents) {
            return $this->eachMatchingModel(function ($model) use ($values) {
                $model->update($values);
            });
        }

        return $this->getSearchOrUpdateBuilder(isQueryBuilder: $usingQueryBuilder)->update($values);
    }

    /**
     * Insert a single row (Eloquent `create`) or many rows (`insert`).
     *
     * A list of associative arrays is treated as a bulk insert. A single associative
     * array is created through Eloquent unless `$usingQueryBuilder` is true.
     *
     * @param  array  $values
     * @param  bool  $usingQueryBuilder
     * @return \Illuminate\Database\Eloquent\Model|bool
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performInsert(array $values, bool $usingQueryBuilder = false)
    {
        $builder = $this->getSearchOrUpdateBuilder(isQueryBuilder: $usingQueryBuilder);
        $isMany  = array_is_list($values) && isset($values[0]) && is_array($values[0]);

        if ($isMany && $this->usingModelEvents) {
            $eloquent = $builder instanceof Model ? $builder->newQuery() : $builder;

            foreach ($values as $row) {
                $eloquent->create($row);
            }

            return true;
        }

        if ($usingQueryBuilder || $isMany) {
            $query = $builder instanceof Model
                ? $builder->newQuery()->getQuery()
                : ($builder instanceof EloquentBuilder ? $builder->getQuery() : $builder);

            return $query->insert($values);
        }

        $eloquent = $builder instanceof Model ? $builder->newQuery() : $builder;
        $model    = $eloquent->create($values);

        $this->modelForUpdate = $model;

        return $model;
    }

    /**
     * Insert or update records using unique columns to match existing rows.
     *
     * When `usingModelEvents()` is enabled, each row is persisted via `updateOrCreate`.
     *
     * @param  array  $values
     * @param  array  $uniqueBy
     * @param  array|null  $update
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function performUpsert(array $values, array $uniqueBy, ?array $update = null)
    {
        $rows = array_is_list($values) && isset($values[0]) && is_array($values[0])
            ? $values
            : [$values];

        $builder = $this->getSearchOrUpdateBuilder();

        if ($this->usingModelEvents) {
            $eloquent = $builder instanceof Model ? $builder->newQuery() : $builder;

            foreach ($rows as $row) {
                $match = [];

                foreach ($uniqueBy as $column) {
                    $match[$column] = $row[$column] ?? null;
                }

                $payload = $update === null ? $row : array_merge(
                    array_intersect_key($row, array_flip($update)),
                    $match
                );

                $eloquent->updateOrCreate($match, $payload);
            }

            return count($rows);
        }

        $query = $builder instanceof Model ? $builder->newQuery() : $builder;

        return $query->upsert($rows, $uniqueBy, $update);
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
        if ($this->usingModelEvents) {
            return $this->eachMatchingModel(function ($model) {
                $model->delete();
            });
        }

        return $this->getSearchOrUpdateBuilder(isQueryBuilder: $usingQueryBuilder)->delete();
    }

    /**
     * Restore soft-deleted records. Combine with `onlyTrashed()` to target deleted rows.
     *
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function restore()
    {
        if (method_exists($this, 'assertUsesSoftDeletes')) {
            $this->assertUsesSoftDeletes();
        }

        if ($this->usingModelEvents) {
            return $this->eachMatchingModel(function ($model) {
                $model->restore();
            });
        }

        $builder = $this->getSearchOrUpdateBuilder();

        if ($builder instanceof Model) {
            return $builder->restore() ? 1 : 0;
        }

        return (int) $builder->restore();
    }

    /**
     * Permanently delete records, bypassing soft deletes.
     *
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function forceDelete()
    {
        if (method_exists($this, 'assertUsesSoftDeletes')) {
            $this->assertUsesSoftDeletes();
        }

        if ($this->usingModelEvents) {
            return $this->eachMatchingModel(function ($model) {
                $model->forceDelete();
            });
        }

        $builder = $this->getSearchOrUpdateBuilder();

        if ($builder instanceof Model) {
            return $builder->forceDelete() ? 1 : 0;
        }

        return (int) $builder->forceDelete();
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
        if (! empty($this->modelForUpdate)) {
            foreach ($attributes as $column => $value) {
                $this->modelForUpdate->{$column} += $value;
            }

            $this->modelForUpdate->save();

            return $this;
        }

        if ($this->usingModelEvents) {
            $this->eachMatchingModel(function ($model) use ($attributes) {
                foreach ($attributes as $column => $value) {
                    $model->{$column} += $value;
                }

                $model->save();
            });

            return $this;
        }

        /**
         * @see https://php.net/manual/en/closure.call.php
         */
        $extra = ! $usingQueryBuilder
            ? (fn($args) => $this->addUpdatedAtColumn($args))->call($this->getSearchOrUpdateBuilder(), [])
            : [];

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
        if (! empty($this->modelForUpdate)) {
            foreach ($attributes as $column => $value) {
                $this->modelForUpdate->{$column} -= $value;
            }

            $this->modelForUpdate->save();

            return $this;
        }

        if ($this->usingModelEvents) {
            $this->eachMatchingModel(function ($model) use ($attributes) {
                foreach ($attributes as $column => $value) {
                    $model->{$column} -= $value;
                }

                $model->save();
            });

            return $this;
        }

        /**
         * @see https://php.net/manual/en/closure.call.php
         */
        $extra = ! $usingQueryBuilder
            ? (fn($args) => $this->addUpdatedAtColumn($args))->call($this->getSearchOrUpdateBuilder(), [])
            : [];

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
        if (! empty($this->modelForUpdate)) {
            $this->modelForUpdate->update(array_fill_keys($attributes, 0));

            return $this;
        }

        if ($this->usingModelEvents) {
            $this->eachMatchingModel(function ($model) use ($attributes) {
                $model->update(array_fill_keys($attributes, 0));
            });

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
        if (! empty($this->modelForUpdate)) {
            $columns = $this->modelForUpdate->only($attributes);

            $toggle = array_map(fn($value) => ! $value, $columns);

            $this->modelForUpdate->update($toggle);

            return $this;
        }

        if ($this->usingModelEvents) {
            $this->eachMatchingModel(function ($model) use ($attributes) {
                $columns = $model->only($attributes);

                $model->update(array_map(fn($value) => ! $value, $columns));
            });

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
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchOrUpdateBuilder($relationship = null, $isQueryBuilder = false)
    {
        if (method_exists($this, 'applyRequestConstraints')) {
            $this->applyRequestConstraints();
        }

        // If the "setRelationship" method exists, it means the request is coming
        // from the "Searchable" context since the "Updatable" trait is used there.
        if (! empty($relationship) && method_exists($this, 'setRelationship')) {
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
    protected function prepareUpdateQuery($usingQueryBuilder = false)
    {
        $model = $this->getUpdatableModel();

        // If the developer has set a model for update, it takes precedence.
        if (! empty($model) && $model->exists) {
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
        if ($query instanceof EloquentBuilder || $query instanceof QueryBuilder) {
            $this->searchOrUpdateQuery = $query;
        }

        return $this;
    }

    /**
     * Reset the internal updatable state.
     *
     * @return $this
     */
    public function flushUpdatable()
    {
        $this->searchOrUpdateQuery = null;
        $this->modelForUpdate      = null;
        $this->usingModelEvents    = false;

        return $this;
    }

    /**
     * Walk matching eloquent models and persist changes through the model instance.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Model): void  $callback
     * @return int
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function eachMatchingModel(callable $callback)
    {
        $builder = $this->getSearchOrUpdateBuilder(isQueryBuilder: false);

        if ($builder instanceof Model) {
            if ($builder->exists) {
                $callback($builder);

                return 1;
            }

            $builder = $builder->newQuery();
        }

        $count = 0;

        $builder->chunkById(100, function ($models) use ($callback, &$count) {
            foreach ($models as $model) {
                $callback($model);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Fetch the builder instance.
     *
     * @param  bool  $isQueryBuilder
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function fetchBuilder(bool $isQueryBuilder = false)
    {
        $builder = $this->getSearchOrUpdateBuilder(isQueryBuilder: $isQueryBuilder);

        if (! $isQueryBuilder && method_exists($this, 'applyEloquentExtras')) {
            return $this->applyEloquentExtras($builder);
        }

        return $builder;
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
