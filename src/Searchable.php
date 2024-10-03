<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\ShouldBuildQueries;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;
use Illuminate\Database\Eloquent\Builder;
use Ramadan\EasyModel\Concerns\HasModel;

trait Searchable
{
    use HasModel, ShouldBuildQueries;

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
     * @return $this
     */
    public function addWheres(array $wheres)
    {
        return $this->buildQueryUsingWheres($wheres);
    }

    /**
     * Add a basic "or where" clause to the query.
     *
     * @param  array  $wheres
     * @return $this
     */
    public function addOrWheres($wheres)
    {
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereHas(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query);

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereHas(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query, 'orWhereHas');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query, 'whereDoesntHave');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query, 'orWhereDoesntHave');

        return $this;
    }

    /**
     * Add a basic where clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereRelation(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereRelations($wheres, $query, 'whereRelation');

        return $this;
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereRelation(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereRelations($wheres, $query, 'orWhereRelation');

        return $this;
    }

    /**
     * Check if the query has been set.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function checkQueryExistence($query = null)
    {
        if (!empty($query)) {
            $this->query = $query;
        }

        $this->guessModel();

        if (empty($this->getQuery())) {
            throw new InvalidSearchableModel('Provide a model to search in.');
        }
    }

    /**
     * Start building a new query or chain the existing one.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function getQuery()
    {
        if (!empty($this->query)) {
            return $this->query;
        }

        if (gettype($this->getModel()) === 'string') {
            return $this->getModel()::query();
        }

        return $this->getModel()->{$this->getRelationship()}();
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation
     */
    public function execute()
    {
        return $this->getQuery();
    }
}
