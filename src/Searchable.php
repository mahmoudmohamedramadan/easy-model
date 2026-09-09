<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\Search\ShouldBuildQueries;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Traits\Conditionable;
use Ramadan\EasyModel\Concerns\Search\HasModel as SearchableModel;
use Ramadan\EasyModel\Concerns\Search\InteractsWithRequest;
use Ramadan\EasyModel\Concerns\Search\Orderable;
use Ramadan\EasyModel\Concerns\Search\PresentsResults;
use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait Searchable
{
    use SearchableModel, Orderable, ShouldBuildQueries, InteractsWithRequest, PresentsResults, Conditionable, Updatable;

    /**
     * Regular expression pattern used to match relational operators (e.g., >, <, =, >=, <=).
     *
     * @var string
     */
    protected $operatorsPattern = '/[><=]+/';

    /**
     * Add a basic "where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWheres(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWheres($wheres);
    }

    /**
     * Add a basic "or where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWheres(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWheres($wheres, 'orWhere');
    }

    /**
     * Add a "where in" clause to the query for one or more columns.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereIn(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this->applyMassWhere($wheres, 'whereIn', $query);
    }

    /**
     * Add a "where not in" clause to the query for one or more columns.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereNotIn(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this->applyMassWhere($wheres, 'whereNotIn', $query);
    }

    /**
     * Add a "where null" clause to the query for one or more columns.
     *
     * @param  array  $columns
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereNull(array $columns, QueryBuilder|EloquentBuilder|null $query = null)
    {
        $builder = $this->setSearchableQuery($query)->getSearchableQueryBuilder();
        $builder->whereNull($columns);
        $this->queryBuilder = $builder;

        return $this;
    }

    /**
     * Add a "where not null" clause to the query for one or more columns.
     *
     * @param  array  $columns
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereNotNull(array $columns, QueryBuilder|EloquentBuilder|null $query = null)
    {
        $builder = $this->setSearchableQuery($query)->getSearchableQueryBuilder();
        $builder->whereNotNull($columns);
        $this->queryBuilder = $builder;

        return $this;
    }

    /**
     * Add a "where between" clause to the query for one or more columns.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereBetween(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this->applyMassWhere($wheres, 'whereBetween', $query);
    }

    /**
     * Search for a keyword across multiple columns using a single grouped OR/LIKE clause.
     *
     * Dotted columns (e.g. `posts.title`) are matched against the related table via `orWhereHas`.
     *
     * @param  string|null  $keyword
     * @param  array  $columns
     * @param  bool  $strict
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addKeywordSearch(?string $keyword, array $columns, bool $strict = false)
    {
        if ($keyword === null || $keyword === '' || empty($columns)) {
            return $this;
        }

        $eloquent = $this->getSearchableEloquentBuilder();

        if ($eloquent instanceof Model) {
            $eloquent = $eloquent->newQuery();
        }

        $eloquent->where(function ($inner) use ($keyword, $columns, $strict) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    $segments = explode('.', $column);
                    $field    = array_pop($segments);
                    $relation = implode('.', $segments);

                    $inner->orWhereHas($relation, function ($query) use ($field, $keyword, $strict) {
                        $strict
                            ? $query->where($field, '=', $keyword)
                            : $query->where($field, 'LIKE', '%' . $keyword . '%');
                    });

                    continue;
                }

                $strict
                    ? $inner->orWhere($column, '=', $keyword)
                    : $inner->orWhere($column, 'LIKE', '%' . $keyword . '%');
            }
        });

        $this->eloquentBuilder = $eloquent;
        $this->queryBuilder    = $eloquent->getQuery();

        return $this;
    }

    /**
     * Add a "where date" clause to the query for one or more columns.
     *
     * Each entry must be `[column, value]` or `[column, operator, value]`.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereDate(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingTupleWheres($wheres, 'whereDate');
    }

    /**
     * Constrain a column to a closed date/time period.
     *
     * @param  string  $column
     * @param  mixed  $from
     * @param  mixed  $to
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWherePeriod(string $column, mixed $from, mixed $to, QueryBuilder|EloquentBuilder|null $query = null)
    {
        $builder = $this->setSearchableQuery($query)->getSearchableQueryBuilder();
        $builder->whereBetween($column, [$from, $to]);
        $this->queryBuilder = $builder;

        return $this;
    }

    /**
     * Add a "where JSON contains" clause to the query for one or more columns.
     *
     * Each entry must be a `[column => value]` pair.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereJsonContains(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        $builder = $this->setSearchableQuery($query)->getSearchableQueryBuilder();

        foreach ($wheres as $where) {
            if (! is_array($where) || $where === []) {
                throw InvalidArrayStructure::invalidColumnValuesTuple('whereJsonContains');
            }

            $column = array_key_first($where);
            $value  = $where[$column];

            if (! is_string($column)) {
                throw InvalidArrayStructure::invalidColumnValuesTuple('whereJsonContains');
            }

            $builder->whereJsonContains($column, $value);
        }

        $this->queryBuilder = $builder;

        return $this;
    }

    /**
     * Add the "whereHas", "whereDoesntHave" and "whereRelation" clauses to the query.
     *
     * @param  array  $has
     * @param  array  $doesntHave
     * @param  array  $relation
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addRelationConditions(
        array $has = [],
        array $doesntHave = [],
        array $relation = [],
        QueryBuilder|EloquentBuilder|null $query = null
    ) {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingRelationConditions($has, $doesntHave, $relation);
    }

    /**
     * Add the "orWhereHas", "orWhereDoesntHave" and "orWhereRelation" clauses to the query.
     *
     * @param  array  $has
     * @param  array  $doesntHave
     * @param  array  $relation
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrRelationConditions(
        array $has = [],
        array $doesntHave = [],
        array $relation = [],
        QueryBuilder|EloquentBuilder|null $query = null
    ) {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingRelationConditions($has, $doesntHave, $relation, 'orWhere');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereHas(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWhereHas(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres, 'orWhereHas');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereDoesntHave(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres, 'whereDoesntHave');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWhereDoesntHave(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres, 'orWhereDoesntHave');
    }

    /**
     * Add a basic "where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereRelation(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereRelation($wheres, 'whereRelation');
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWhereRelation(array $wheres, QueryBuilder|EloquentBuilder|null $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereRelation($wheres, 'orWhereRelation');
    }

    /**
     * Start building a new eloquent builder or chain the existing one.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchableEloquentBuilder()
    {
        $this->resolveModel();

        $model = $this->getSearchableModel();

        if (! empty($model) && $model->exists && empty($this->eloquentBuilder) && empty($this->queryBuilder)) {
            return $model;
        }

        $relationship = $this->getRelationship();

        // There is no ability to search when providing a relationship
        // and the model is anonymous (e.g., User::class, new User).
        if (! empty($relationship) && ! $model->exists) {
            throw InvalidModel::cannotSearchAnonymousRelation();
        }

        if (empty($this->eloquentBuilder) && ! empty($this->queryBuilder)) {
            // When the relationship is provided, we will start a new query and set the model
            // with the given relationship using "getRelated" method on it.
            $this->modelOrRelation = $this->resolveModelOrRelation($relationship, $model);

            $this->eloquentBuilder = $this->modelOrRelation->newQuery()->setQuery($this->queryBuilder);
        }

        if (! empty($this->eloquentBuilder)) {
            return $this->eloquentBuilder;
        }

        return empty($relationship) ? $model->newQuery() : $model->{$relationship}()->getQuery();
    }

    /**
     * Start building a new query builder or chain the existing one.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $givenQuery
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchableQueryBuilder($givenQuery = null)
    {
        $this->setSearchableQuery($givenQuery);

        return ! empty($this->queryBuilder)
            ? $this->queryBuilder
            : $this->getSearchableEloquentBuilder()->getQuery();
    }

    /**
     * Set the given query according to its type.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    public function setSearchableQuery(QueryBuilder|EloquentBuilder|null $query = null)
    {
        if ($query instanceof EloquentBuilder) {
            $this->eloquentBuilder = $query;
            $this->queryBuilder    = $query->getQuery();
        } elseif ($query instanceof QueryBuilder) {
            $this->queryBuilder = $query;
        }

        return $this;
    }

    /**
     * Reset all internal state for the searchable / updatable traits.
     *
     * @return $this
     */
    public function flushSearchable()
    {
        $this->queryBuilder             = null;
        $this->eloquentBuilder          = null;
        $this->modelOrRelation          = null;
        $this->relationship             = null;
        $this->joinedRelationshipTables = [];
        $this->searchOrUpdateQuery      = null;
        $this->modelForUpdate           = null;
        $this->usingModelEvents         = false;

        $this->flushRequestState();
        $this->flushPresentationState();

        return $this;
    }

    /**
     * Execute the query.
     *
     * @param  bool  $iNeedEloquentBuilderInstance
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function execute(bool $iNeedEloquentBuilderInstance = true)
    {
        $this->applyRequestConstraints();

        if (! $iNeedEloquentBuilderInstance) {
            return $this->getSearchableQueryBuilder();
        }

        return $this->applyEloquentExtras($this->getSearchableEloquentBuilder());
    }
}
