<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\ShouldBuildQueries;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;
use Illuminate\Database\Eloquent\Builder;
use Ramadan\EasyModel\Concerns\HasModel;
use Ramadan\EasyModel\Concerns\Orderable;

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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addWheres(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        return $this->buildQueryUsingWheres($wheres);
    }

    /**
     * Add a basic "or where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addOrWheres($wheres, Builder $query = null)
    {
        $this->setQuery($query);

        return $this->buildQueryUsingWheres($wheres, 'orWhere');
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addAllWheres(
        array $whereHas = [],
        array $whereDoesntHave = [],
        array $whereRelation = [],
        Builder $query = null
    ) {
        $this->setQuery($query);

        return $this->buildQueryUsingAllWheres($whereHas, $whereDoesntHave, $whereRelation);
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addAllOrWheres(
        array $whereHas = [],
        array $whereDoesntHave = [],
        array $whereRelation = [],
        Builder $query = null
    ) {
        $this->setQuery($query);

        return $this->buildQueryUsingAllWheres($whereHas, $whereDoesntHave, $whereRelation, 'orWhere');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addWhereHas(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereHasAndDoesntHave($wheres);

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addOrWhereHas(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, 'orWhereHas');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, 'whereDoesntHave');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addOrWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereHasAndDoesntHave($wheres, 'orWhereDoesntHave');

        return $this;
    }

    /**
     * Add a basic where clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addWhereRelation(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereRelation($wheres, 'whereRelation');

        return $this;
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addOrWhereRelation(array $wheres, Builder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereRelation($wheres, 'orWhereRelation');

        return $this;
    }

    /**
     * Start building a new eloquent query or chain the existing one.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function getEloquentBuilder()
    {
        $this->guessModel();

        if (empty($this->getModel())) {
            throw new InvalidSearchableModel('Provide a model to search in.');
        }

        if (!empty($this->queryBuilder)) {
            $this->eloquentBuilder = new Builder($this->queryBuilder);
            $this->eloquentBuilder->setModel($this->getModel());

            return $this->eloquentBuilder;
        }

        // If the provided model was a string it means, the developer needs to search
        // in a whole model (e.g. User::class).
        if (empty($this->getModel()->getTable())) {
            return $this->getModel()->query();
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
     * @param  \Illuminate\Database\Eloquent\Builder|null  $eloquentBuilder
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function getQueryBuilder($eloquentBuilder = null)
    {
        $this->setQuery($eloquentBuilder, true);

        if (!empty($this->queryBuilder)) {
            return $this->queryBuilder;
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
     * Set the given query according to the given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  bool  $isQueryBuilder
     * @return void
     */
    protected function setQuery($query = null, $isQueryBuilder = false)
    {
        if (!empty($query) && $isQueryBuilder) {
            $this->queryBuilder = $query->getQuery();
        } elseif (!empty($query) && !$isQueryBuilder) {
            $this->eloquentBuilder = $query;
        }
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function execute()
    {
        return $this->queryBuilder;
    }
}
