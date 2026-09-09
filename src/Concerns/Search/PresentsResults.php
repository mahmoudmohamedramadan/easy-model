<?php

namespace Ramadan\EasyModel\Concerns\Search;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

trait PresentsResults
{
    /**
     * Relationships to eager load when the eloquent builder is resolved.
     *
     * @var array
     */
    protected array $eagerLoads = [];

    /**
     * Relationships to eager-load counts for when the eloquent builder is resolved.
     *
     * @var array
     */
    protected array $eagerLoadCounts = [];

    /**
     * Extra columns to add to the select list.
     *
     * @var array<int, string>
     */
    protected array $selectColumns = [];

    /**
     * Eager load the given relationships when the query is executed.
     *
     * @param  array  $relations
     * @return $this
     */
    public function addWith(array $relations)
    {
        $this->eagerLoads = array_merge($this->eagerLoads, $relations);

        return $this;
    }

    /**
     * Eager load relationship counts when the query is executed.
     *
     * @param  array  $relations
     * @return $this
     */
    public function addWithCount(array $relations)
    {
        $this->eagerLoadCounts = array_merge($this->eagerLoadCounts, $relations);

        return $this;
    }

    /**
     * Add columns to the query's select list.
     *
     * @param  array<int, string>  $columns
     * @return $this
     */
    public function addSelect(array $columns)
    {
        $this->selectColumns = array_merge($this->selectColumns, $columns);

        return $this;
    }

    /**
     * Paginate the query results.
     *
     * @param  int|\Closure|null  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        return $this->execute()->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Paginate the query results into a simple paginator.
     *
     * @param  int|null  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Pagination\Paginator
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function simplePaginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): Paginator
    {
        return $this->execute()->simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Paginate the query results into a cursor paginator.
     *
     * @param  int|null  $perPage
     * @param  array|string  $columns
     * @param  string  $cursorName
     * @param  \Illuminate\Pagination\Cursor|string|null  $cursor
     * @return \Illuminate\Pagination\CursorPaginator
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function cursorPaginate($perPage = 15, $columns = ['*'], $cursorName = 'cursor', $cursor = null): CursorPaginator
    {
        return $this->execute()->cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    /**
     * Get the SQL for the current searchable query.
     *
     * @param  bool  $withBindings
     * @return string
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function toSql(bool $withBindings = false): string
    {
        $builder = $this->execute();

        if ($builder instanceof Model) {
            $builder = $builder->newQuery()->whereKey($builder->getKey());
        }

        if ($withBindings && method_exists($builder, 'toRawSql')) {
            return $builder->toRawSql();
        }

        return $builder->toSql();
    }

    /**
     * Apply eager loads, counts, and extra select columns to an eloquent builder or model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder  $builder
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
     */
    protected function applyEloquentExtras($builder)
    {
        if ($builder instanceof Model) {
            if (! empty($this->eagerLoads)) {
                $builder->load($this->eagerLoads);
            }

            return $builder;
        }

        if (! empty($this->eagerLoads)) {
            $builder->with($this->eagerLoads);
        }

        if (! empty($this->eagerLoadCounts)) {
            $builder->withCount($this->eagerLoadCounts);
        }

        if (! empty($this->selectColumns)) {
            $builder->addSelect($this->selectColumns);
        }

        return $builder;
    }

    /**
     * Reset presentation state.
     *
     * @return void
     */
    protected function flushPresentationState()
    {
        $this->eagerLoads      = [];
        $this->eagerLoadCounts = [];
        $this->selectColumns   = [];
    }
}
