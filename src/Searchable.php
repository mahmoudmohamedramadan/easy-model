<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\ShouldBuildQueries;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
    public function addWheres(array $wheres, EloquentBuilder $query = null)
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
    public function addOrWheres($wheres, EloquentBuilder $query = null)
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
        EloquentBuilder $query = null
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
        EloquentBuilder $query = null
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
    public function addWhereHas(array $wheres, EloquentBuilder $query = null)
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
    public function addOrWhereHas(array $wheres, EloquentBuilder $query = null)
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
    public function addWhereDoesntHave(array $wheres, EloquentBuilder $query = null)
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
    public function addOrWhereDoesntHave(array $wheres, EloquentBuilder $query = null)
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
    public function addWhereRelation(array $wheres, EloquentBuilder $query = null)
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
    public function addOrWhereRelation(array $wheres, EloquentBuilder $query = null)
    {
        $this->setQuery($query);

        $this->buildQueryUsingWhereRelation($wheres, 'orWhereRelation');

        return $this;
    }

    /**
     * Start building a new eloquent builder or chain the existing one.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $givenQuery
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function getEloquentBuilder($givenQuery = null)
    {
        $this->setQuery($givenQuery);

        if (empty($this->eloquentBuilder) && !empty($this->queryBuilder)) {
            $this->eloquentBuilder = new EloquentBuilder($this->queryBuilder);
            $this->eloquentBuilder->setModel($this->getModel());
        }

        if (!empty($this->eloquentBuilder)) {
            return $this->eloquentBuilder;
        }

        $this->guessModel();

        if (empty($this->getModel())) {
            throw new InvalidSearchableModel('Provide a model to search in.');
        }

        // If the provided model was a string it means, the developer needs to search
        // in a whole model (e.g. User::class), and according to the new rules i'm getting
        // an anonymous model instance object (e.g. new User) so, it's table name will be empty.
        if (empty($this->getModel()->getTable())) {
            return $this->getModel()->query();
        }

        // If there is no relationship provided, it means that the developer needs to search
        // in a single model instance (e.g. User::first()).
        // Otherwise, it means that he needs to search in a single model instance relationship
        // (e.g. User::first()->posts()).
        return empty($this->getRelationship()) ? $this->getModel() : $this->getModel()->{$this->getRelationship()}();
    }

    /**
     * Start building a new query builder or chain the existing one.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $givenQuery
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function getQueryBuilder($givenQuery = null)
    {
        $this->setQuery($givenQuery);

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
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return void
     */
    protected function setQuery($query = null)
    {
        if ($query instanceof EloquentBuilder) {
            $this->queryBuilder = $query->getQuery();
        } elseif ($query instanceof QueryBuilder) {
            $this->queryBuilder = $query;
        }
    }

    /**
     * Execute the query.
     *
     * @param  bool  $ineedEloquentBuilderInstance
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function execute(bool $ineedEloquentBuilderInstance = true)
    {
        return $ineedEloquentBuilderInstance ? $this->getEloquentBuilder() : $this->getQueryBuilder();
    }
}
