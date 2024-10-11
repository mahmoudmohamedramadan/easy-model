<?php

namespace Ramadan\EasyModel\Concerns;

use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;

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
        $this->queryBuilder = $this
            ->getQueryBuilder()
            ->whereNested(function ($query) use ($wheres, $method) {
                foreach ($wheres as $key => $value) {
                    if (is_numeric($key) && is_array($value)) {
                        $query->{$method}(...array_values($value));
                    } else {
                        $query->{$method}($key, '=', $value, $method === 'where' ? 'and' : 'or');
                    }
                }
            }, $method === 'where' ? 'and' : 'or');

        return $this;
    }

    /**
     * Build the query using all "where" and "or where" clauses that are used in relationships.
     *
     * @param  array  $whereHas
     * @param  array  $whereDoesntHave
     * @param  array  $whereRelation
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function buildQueryUsingAllWheres(
        $whereHas = [],
        $whereDoesntHave = [],
        $whereRelation = [],
        $method = 'where'
    ) {
        if (!empty($whereHas)) {
            $this->buildQueryUsingWhereHasAndDoesntHave($whereHas, "{$method}Has");
        }

        if (!empty($whereDoesntHave)) {
            $this->buildQueryUsingWhereHasAndDoesntHave($whereDoesntHave, "{$method}DoesntHave");
        }

        if (!empty($whereRelation)) {
            $this->buildQueryUsingWhereRelation($whereRelation, "{$method}Relation");
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

        if (!in_array(strtolower($operator), $this->getAllowedOperators(), true)) {
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
