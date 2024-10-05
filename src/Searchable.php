<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\ShouldBuildQueries;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;
use Illuminate\Database\Eloquent\Builder;
use Ramadan\EasyModel\Concerns\HasModel;
use Ramadan\EasyModel\Concerns\Orderable;
use Ramadan\EasyModel\Exceptions\InvalidQuery;

trait Searchable
{
    use Orderable, HasModel, ShouldBuildQueries;

    /**
     * The allowed operators.
     *
     * @var array
     */
    protected $allowedOperators =  [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'like',
        'not like',
        'ilike',
        'not ilike',
        'rlike',
        'not rlike',
    ];

    /**
     * The operators pattern.
     *
     * @var string
     */
    protected $operatorsPattern = '/[><=]+/';

    /**
     * Get the allowed operators.
     *
     * @return array
     */
    public function getAllowedOperators()
    {
        return $this->allowedOperators;
    }

    /**
     * Add a basic "where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addWheres(array $wheres, Builder $query = null)
    {
        return $this->buildQueryUsingWheres($wheres, $query);
    }

    /**
     * Add a basic "or where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addOrWheres($wheres, Builder $query = null)
    {
        return $this->buildQueryUsingWheres($wheres, $query, 'orWhere');
    }

    /**
     * Add the "whereHas", "whereDoesntHave" and "whereRelation" clauses to the query.
     *
     * @param  array  $whereHas
     * @param  array  $whereDoesntHave
     * @param  array  $whereRelation
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addAllWheres(
        array $whereHas = [],
        array $whereDoesntHave = [],
        array $whereRelation = [],
        Builder $query = null
    ) {
        return $this->buildQueryUsingAllWheres($whereHas, $whereDoesntHave, $whereRelation, $query);
    }

    /**
     * Add the "orWhereHas", "orWhereDoesntHave" and "orWhereRelation" clauses to the query.
     *
     * @param  array  $whereHas
     * @param  array  $whereDoesntHave
     * @param  array  $whereRelation
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addAllOrWheres(
        array $whereHas = [],
        array $whereDoesntHave = [],
        array $whereRelation = [],
        Builder $query = null
    ) {
        return $this->buildQueryUsingAllWheres($whereHas, $whereDoesntHave, $whereRelation, $query, 'orWhere');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereHas(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, $query);

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereHas(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, $query, 'orWhereHas');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, $query, 'whereDoesntHave');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, $query, 'orWhereDoesntHave');

        return $this;
    }

    /**
     * Add a basic where clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereRelation(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereRelation($wheres, $query, 'whereRelation');

        return $this;
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereRelation(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereRelation($wheres, $query, 'orWhereRelation');

        return $this;
    }

    /**
     * Check if the query has been set.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string|null  $method
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function checkQueryExistence($query = null, $method = null)
    {
        if (!empty($query)) {
            $this->query = $query->getQuery();
        }

        if (str_starts_with($method, 'orWhere') && empty($this->query)) {
            throw new InvalidQuery;
        }

        $this->guessModel();

        if (empty($this->getQueryBuilder())) {
            throw new InvalidSearchableModel('Provide a model to search in.');
        }
    }

    /**
     * Start building a new eloquent query or chain the existing one.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getEloquentBuilder()
    {
        // If the provided model was a string it means, the developer needs to search
        // in a whole model (e.g. User::class).
        if (is_string($this->getModel())) {
            return $this->getModel()::query();
        }

        // But, in case there is no relationship provided, it means that
        // the developer needs to search in a single model instance (e.g. User::first()).
        // Otherwise, it means that the developer needs to search in
        // a single model instance relationships (e.g. User::first()->posts()).
        return empty($this->getRelationship()) ? $this->getModel() : $this->getModel()->{$this->getRelationship()}();
    }

    /**
     * Start building a new query or chain the existing one.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getQueryBuilder()
    {
        if (!empty($this->query)) {
            return $this->query;
        }

        $query = $this->getEloquentBuilder()->getQuery();

        if (empty($this->getRelationship())) {
            return $query;
        }

        // In case there is a relationship, we will invoke the `getQuery` twice where the first time
        // to get a eloquent builder instance and the second to get a query builder instance.
        return $query->getQuery();
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function execute()
    {
        return $this->getQueryBuilder();
    }
}
