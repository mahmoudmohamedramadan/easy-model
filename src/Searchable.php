<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\ShouldBuildQueries;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Ramadan\EasyModel\Concerns\HasModel;
use Ramadan\EasyModel\Concerns\Orderable;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;

trait Searchable
{
    use HasModel, Orderable, ShouldBuildQueries;

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
     * @param  array  $has
     * @param  array  $doesntHave
     * @param  array  $relation
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addRelationConditions(
        array $has = [],
        array $doesntHave = [],
        array $relation = [],
        EloquentBuilder $query = null
    ) {
        $this->setQuery($query);

        return $this->buildQueryUsingRelationConditions($has, $doesntHave, $relation);
    }

    /**
     * Add the "orWhereHas", "orWhereDoesntHave" and "orWhereRelation" clauses to the query.
     *
     * @param  array  $has
     * @param  array  $doesntHave
     * @param  array  $relation
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function addOrRelationConditions(
        array $has = [],
        array $doesntHave = [],
        array $relation = [],
        EloquentBuilder $query = null
    ) {
        $this->setQuery($query);

        return $this->buildQueryUsingRelationConditions($has, $doesntHave, $relation, 'orWhere');
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
     * Add a basic "where" clause to a relationship query.
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

        $this->guessModel();

        $model        = $this->getModel();
        $relationship = $this->getRelationship();

        if (empty($this->eloquentBuilder) && !empty($this->queryBuilder)) {
            if (empty($relationship)) {
                $this->eloquentBuilder = $model->newQuery()->setQuery($this->queryBuilder);
            } else {
                // When the relationship is provided, we will start a new query and set the model
                // with the given relationship using "getRelated" method on it.
                $this->eloquentBuilder = $model
                    ->newEloquentBuilder($this->queryBuilder)
                    ->setModel($model->{$relationship}()->getRelated());
            }
        }

        if (!empty($this->eloquentBuilder)) {
            return $this->eloquentBuilder;
        }

        // There is no ability to search when providing a relationship
        // and the model is anonymous (e.g., User::class, new User).
        if (!empty($relationship) && !$model->exists) {
            throw new InvalidSearchableModel('Cannot search in a relationship with anonymous model.');
        }

        if (empty($relationship)) {
            return $model->query();
        }

        return $model->{$relationship}()->getQuery();
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

        return $this->getEloquentBuilder()->getQuery();
    }

    /**
     * Set the given query according to its type.
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
     * @param  bool  $iNeedEloquentBuilderInstance
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function execute(bool $iNeedEloquentBuilderInstance = true)
    {
        return $iNeedEloquentBuilderInstance ? $this->getEloquentBuilder() : $this->getQueryBuilder();
    }
}
