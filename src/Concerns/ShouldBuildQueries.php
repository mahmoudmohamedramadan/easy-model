<?php

namespace Ramadan\EasyModel\Concerns;

use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Support\Arr;

trait ShouldBuildQueries
{
    /**
     * The search query builder.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $queryBuilder;

    /**
     * The search eloquent builder.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $eloquentBuilder;

    /**
     * Add a basic "where" and "or where" clause to the query.
     *
     * @param  array  $wheres
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    public function buildQueryUsingWheres($wheres, $method = 'where')
    {
        $queryBuilder = $this->getQueryBuilder();
        foreach ($wheres as $where) {
            $this->{(match (gettype($where)) {
                'object' => 'prepareClosuresForWheres',
                'array'  => 'prepareArraysForWheres',
            })}($where, $queryBuilder, $method);
        }
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Build the query using all "where" and "or where" clauses that are used in relationships.
     *
     * @param  array  $has
     * @param  array  $doesntHave
     * @param  array  $relation
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function buildQueryUsingRelationConditions(
        $has = [],
        $doesntHave = [],
        $relation = [],
        $method = 'where'
    ) {
        if (!empty($has)) {
            $this->buildQueryUsingWhereHasAndDoesntHave($has, "{$method}Has");
        }

        if (!empty($doesntHave)) {
            $this->buildQueryUsingWhereHasAndDoesntHave($doesntHave, "{$method}DoesntHave");
        }

        if (!empty($relation)) {
            $this->buildQueryUsingWhereRelation($relation, "{$method}Relation");
        }

        return $this;
    }

    /**
     * Build the query using "whereHas", and "whereDoesntHave" queries.
     *
     * @param  array  $wheres
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function buildQueryUsingWhereHasAndDoesntHave($wheres, $method = 'whereHas')
    {
        foreach ($wheres as $relation => $closure) {
            if (!is_string($closure) && !is_callable($closure)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            $paramters = $this->prepareParamtersForWhereHasAndDoesntHave(
                is_string($closure) ? $closure : $relation,
                $method
            );

            $this->queryBuilder = $this->buildQueryUsingHas(
                relation: $paramters['relation'],
                operator: $paramters['operator'],
                count: $paramters['count'],
                boolean: str_starts_with($method, 'orWhere') ? 'or' : 'and',
                closure: is_callable($closure) ? $closure : null
            );
        }

        return $this;
    }

    /**
     * Build the query using "whereRelation" queries.
     *
     * @param  array  $wheres
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function buildQueryUsingWhereRelation($wheres, $method = 'whereRelation')
    {
        foreach ($wheres as $relation => $closure) {
            if ((!is_string($relation) && !is_callable($closure)) && !is_array($closure)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            $paramters = $this->prepareParamtersForWhereRelation($relation, $closure);

            $this->queryBuilder = $this->buildQueryUsingHas(
                relation: $paramters['relation'],
                boolean: str_starts_with($method, 'orWhere') ? 'or' : 'and',
                closure: function ($query) use ($paramters) {
                    if (is_callable($column = $paramters['column'])) {
                        $column($query);
                    } else {
                        $query->where($paramters['column'], $paramters['operator'], $paramters['value']);
                    }
                }
            );
        }

        return $this;
    }

    /**
     * Build the query using "has" queries.
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @param  \Closure|null  $closure
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function buildQueryUsingHas($relation, $operator = '>=', $count = 1, $boolean = 'and', $closure = null)
    {
        return $this
            ->getEloquentBuilder()
            ->has(...func_get_args())
            ->getQuery();
    }

    /**
     * Prepare a nested of "where" clauses using the given closure.
     *
     * @param \Closure  $where
     * @param \Illuminate\Database\Query\Builder  $queryBuilder
     * @param string  $method
     * @return void
     */
    protected function prepareClosuresForWheres($where, $queryBuilder, $method = 'where')
    {
        $where($query = $queryBuilder->forNestedWhere());
        $boolean = $method === 'where' ? 'and' : 'or';

        $queryBuilder->addNestedWhereQuery($query, $boolean);
    }

    /**
     * Prepare an array of conditions to be inserted into the "where" clause.
     *
     * @param array  $where
     * @param \Illuminate\Database\Query\Builder  $queryBuilder
     * @param string  $method
     * @return void
     */
    protected function prepareArraysForWheres($where, $queryBuilder, $method = 'where')
    {
        $type = 'Basic';

        $column   = $where[0];
        $operator = count($where) === 3 ? $where[1] : '=';
        $value    = count($where) === 3 ? $where[2] : $where[1];
        $boolean  = $method === 'where' ? 'and' : 'or';

        if (! $value instanceof ExpressionContract) {
            $queryBuilder->addBinding(is_array($value) ? reset(Arr::flatten($value)) : $value, 'where');
        }

        $queryBuilder->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');
    }

    /**
     * Prepare the "whereHas", and "whereDoesntHave" parameters.
     *
     * @param  string  $subject
     * @param  string  $method
     * @return array
     */
    protected function prepareParamtersForWhereHasAndDoesntHave(string $subject, string $method)
    {
        if (in_array($method, ['whereDoesntHave', 'orWhereDoesntHave'], true)) {
            return [
                'relation' => $subject,
                'operator' => '<',
                'count'    => 1,
            ];
        }

        preg_match($this->operatorsPattern, $subject, $operator);
        $matches = preg_split($this->operatorsPattern, $subject);

        return [
            'relation' => $matches[0],
            'operator' => array_key_exists(0, $operator) ? $operator[0] : '>=',
            'count'    => array_key_exists(1, $matches) ? $matches[1] : 1,
        ];
    }

    /**
     * Prepare the "whereRelation" parameters.
     *
     * @param  string  $relation
     * @param  array|\Closure  $closure
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareParamtersForWhereRelation(string $relation, array|\Closure $closure)
    {
        if (is_string($relation) && is_callable($closure)) {
            return [
                'relation' => $relation,
                'column'   => $closure,
                'operator' => '>=',
                'value'    => 1
            ];
        }

        $relation = $closure[0];
        $column   = $closure[1];
        $operator = count($closure) === 4 ? $closure[2] : '=';
        $value    = count($closure) === 4 ? $closure[3] : $closure[2];

        if (!in_array(strtolower($operator), $this->allowedOperators, true)) {
            throw new InvalidArrayStructure("The `{$operator}` is not a valid operator.");
        }

        return [
            'relation' => $relation,
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value
        ];
    }
}
