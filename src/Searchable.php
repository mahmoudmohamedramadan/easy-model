<?php

namespace Ramadan\EasyModel;

use Ramadan\EasyModel\Concerns\Search\ShouldBuildQueries;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Ramadan\EasyModel\Concerns\Search\HasModel as SearchableModel;
use Ramadan\EasyModel\Concerns\Search\Orderable;
use Ramadan\EasyModel\Exceptions\InvalidModel;

trait Searchable
{
    use SearchableModel, Orderable, ShouldBuildQueries, Updatable;

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
     * Add a basic "where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWheres(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWheres($wheres);
    }

    /**
     * Add a basic "or where" clause to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWheres(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWheres($wheres, 'orWhere');
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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addRelationConditions(
        array $has = [],
        array $doesntHave = [],
        array $relation = [],
        ?EloquentBuilder $query = null
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
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrRelationConditions(
        array $has = [],
        array $doesntHave = [],
        array $relation = [],
        ?EloquentBuilder $query = null
    ) {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingRelationConditions($has, $doesntHave, $relation, 'orWhere');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereHas(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWhereHas(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres, 'orWhereHas');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereDoesntHave(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres, 'whereDoesntHave');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWhereDoesntHave(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereHasAndDoesntHave($wheres, 'orWhereDoesntHave');
    }

    /**
     * Add a basic "where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addWhereRelation(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereRelation($wheres, 'whereRelation');
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrWhereRelation(array $wheres, ?EloquentBuilder $query = null)
    {
        return $this
            ->setSearchableQuery($query)
            ->buildQueryUsingWhereRelation($wheres, 'orWhereRelation');
    }

    /**
     * Start building a new eloquent builder or chain the existing one.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchableEloquentBuilder()
    {
        $this->resolveModel();

        $model        = $this->getSearchableModel();
        $relationship = $this->getRelationship();

        // There is no ability to search when providing a relationship
        // and the model is anonymous (e.g., User::class, new User).
        if (!empty($relationship) && !$model->exists) {
            throw new InvalidModel("Cannot search in a relationship with anonymous model.");
        }

        if (empty($this->eloquentBuilder) && !empty($this->queryBuilder)) {
            // When the relationship is provided, we will start a new query and set the model
            // with the given relationship using "getRelated" method on it.
            $this->modelOrRelation = $this->resolveModelOrRelation($relationship, $model);

            $this->eloquentBuilder = $this->modelOrRelation->newQuery()->setQuery($this->queryBuilder);
        }

        if (!empty($this->eloquentBuilder)) {
            return $this->eloquentBuilder;
        }

        return empty($relationship) ? $model->newQuery() : $model->{$relationship}()->getQuery();
    }

    /**
     * Start building a new query builder or chain the existing one.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $givenQuery
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    protected function getSearchableQueryBuilder(?EloquentBuilder $givenQuery = null)
    {
        $this->setSearchableQuery($givenQuery);

        return !empty($this->queryBuilder) ?
            $this->queryBuilder :
            $this->getSearchableEloquentBuilder()->getQuery();
    }

    /**
     * Set the given query according to its type.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    public function setSearchableQuery($query = null)
    {
        if ($query instanceof EloquentBuilder) {
            $this->queryBuilder = $query->getQuery();
        } elseif ($query instanceof QueryBuilder) {
            $this->queryBuilder = $query;
        }

        return $this;
    }

    /**
     * Execute the query.
     *
     * @param  bool  $iNeedEloquentBuilderInstance
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function execute(bool $iNeedEloquentBuilderInstance = true)
    {
        return $iNeedEloquentBuilderInstance ?
            $this->getSearchableEloquentBuilder() :
            $this->getSearchableQueryBuilder();
    }
}
