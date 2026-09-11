<?php

namespace Ramadan\EasyModel\Concerns\Search;

use Illuminate\Http\Request;

trait InteractsWithRequest
{
    /**
     * The incoming HTTP request used to hydrate filters, sorts, and includes.
     *
     * @var \Illuminate\Http\Request|null
     */
    protected $request;

    /**
     * Filter keys that may be read from the request.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [];

    /**
     * Sort keys that may be read from the request.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [];

    /**
     * Relationship names that may be eager loaded from the request.
     *
     * @var array<int, string>
     */
    protected array $allowedIncludes = [];

    /**
     * Columns (including dotted relation columns) used for the request `search` query.
     *
     * @var array<int, string>
     */
    protected array $allowedSearchColumns = [];

    /**
     * Whether request constraints have already been applied to the current builder.
     *
     * @var bool
     */
    protected bool $requestConstraintsApplied = false;

    /**
     * Bind an HTTP request (or a query-string array) as the source of filters, sorts, and includes.
     *
     * @param  \Illuminate\Http\Request|array  $request
     * @return $this
     */
    public function fromRequest(Request|array $request)
    {
        $this->request = is_array($request)
            ? Request::create('/', 'GET', $request)
            : $request;

        $this->requestConstraintsApplied = false;

        return $this;
    }

    /**
     * Allowlist of filterable columns or `relation.column` paths.
     *
     * @param  array<int, string>  $filters
     * @return $this
     */
    public function allowedFilters(array $filters)
    {
        $this->allowedFilters = array_values($filters);

        $this->requestConstraintsApplied = false;

        return $this;
    }

    /**
     * Allowlist of sortable columns or `relation.column` paths.
     *
     * @param  array<int, string>  $sorts
     * @return $this
     */
    public function allowedSorts(array $sorts)
    {
        $this->allowedSorts = array_values($sorts);

        $this->requestConstraintsApplied = false;

        return $this;
    }

    /**
     * Allowlist of relationships that may be eager loaded via `include`.
     *
     * @param  array<int, string>  $includes
     * @return $this
     */
    public function allowedIncludes(array $includes)
    {
        $this->allowedIncludes = array_values($includes);

        $this->requestConstraintsApplied = false;

        return $this;
    }

    /**
     * Columns used when the request provides a `search` query parameter.
     *
     * @param  array<int, string>  $columns
     * @return $this
     */
    public function allowedSearch(array $columns)
    {
        $this->allowedSearchColumns = array_values($columns);

        $this->requestConstraintsApplied = false;

        return $this;
    }

    /**
     * Apply allowlisted request constraints once, just before the query is executed.
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function applyRequestConstraints()
    {
        if ($this->requestConstraintsApplied || empty($this->request)) {
            return;
        }

        $this->requestConstraintsApplied = true;

        $this->applyRequestFilters();
        $this->applyRequestSearch();
        $this->applyRequestSorts();
        $this->applyRequestIncludes();
    }

    /**
     * Apply allowlisted `filter` query parameters.
     *
     * Unknown keys are ignored. Scalar values use `=` unless they contain `%` (then `LIKE`).
     * Array values become `whereIn`. Dotted keys are applied as relation constraints.
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function applyRequestFilters()
    {
        $filters = $this->request->input('filter', []);

        if (! is_array($filters) || empty($this->allowedFilters)) {
            return;
        }

        foreach ($filters as $field => $value) {
            if (! is_string($field) || ! in_array($field, $this->allowedFilters, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (str_contains($field, '.')) {
                $this->applyRelationRequestFilter($field, $value);

                continue;
            }

            $this->applyColumnRequestFilter($field, $value);
        }
    }

    /**
     * Apply a filter against a local column.
     *
     * @param  string  $column
     * @param  mixed  $value
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function applyColumnRequestFilter($column, $value)
    {
        if (is_array($value)) {
            $this->addWhereIn([[$column => array_values($value)]]);

            return;
        }

        if (is_string($value) && str_contains($value, '%')) {
            $this->addWheres([[$column, 'LIKE', $value]]);

            return;
        }

        $this->addWheres([[$column, $value]]);
    }

    /**
     * Apply a filter against a related column (`posts.title`).
     *
     * @param  string  $field
     * @param  mixed  $value
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function applyRelationRequestFilter($field, $value)
    {
        $segments = explode('.', $field);
        $column   = array_pop($segments);
        $relation = implode('.', $segments);

        if (is_array($value)) {
            $this->addWhereRelation([
                $relation => fn($query) => $query->whereIn($column, array_values($value)),
            ]);

            return;
        }

        $operator = is_string($value) && str_contains($value, '%') ? 'LIKE' : '=';

        $this->addWhereRelation([
            [$relation, $column, $operator, $value],
        ]);
    }

    /**
     * Apply the `search` query parameter across allowlisted columns.
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function applyRequestSearch()
    {
        $keyword = $this->request->input('search');

        if (! is_string($keyword) || $keyword === '' || empty($this->allowedSearchColumns)) {
            return;
        }

        $this->addKeywordSearch($keyword, $this->allowedSearchColumns);
    }

    /**
     * Apply allowlisted `sort` query parameters (`-column` means descending).
     *
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function applyRequestSorts()
    {
        $sort = $this->request->input('sort');

        if (empty($sort) || empty($this->allowedSorts)) {
            return;
        }

        $parts  = is_array($sort) ? $sort : explode(',', (string) $sort);
        $orders = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part === '') {
                continue;
            }

            $direction = str_starts_with($part, '-') ? 'desc' : 'asc';
            $column    = ltrim($part, '-');

            if (! in_array($column, $this->allowedSorts, true)) {
                continue;
            }

            $orders[] = [$column => $direction];
        }

        if (! empty($orders)) {
            $this->addOrderBy($orders);
        }
    }

    /**
     * Apply allowlisted `include` query parameters as eager loads.
     *
     * @return void
     */
    protected function applyRequestIncludes()
    {
        $include = $this->request->input('include');

        if (empty($include) || empty($this->allowedIncludes)) {
            return;
        }

        $relations = is_array($include) ? $include : explode(',', (string) $include);
        $relations = array_values(array_filter(array_map('trim', $relations)));
        $allowed   = array_values(array_intersect($relations, $this->allowedIncludes));

        if (! empty($allowed)) {
            $this->addWith($allowed);
        }
    }

    /**
     * Reset request-binding state.
     *
     * @return void
     */
    protected function flushRequestState()
    {
        $this->request                    = null;
        $this->allowedFilters             = [];
        $this->allowedSorts               = [];
        $this->allowedIncludes            = [];
        $this->allowedSearchColumns       = [];
        $this->requestConstraintsApplied  = false;
    }
}
