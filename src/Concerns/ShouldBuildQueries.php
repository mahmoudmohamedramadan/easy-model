<?php

namespace Ramadan\EasyModel\Concerns;

use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;
use Ramadan\EasyModel\Exceptions\InvalidQuery;

trait ShouldBuildQueries
{
    /**
     * The search query.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Add a basic "where" clause to the query.
     *
     * @param  array  $column
     * @param  string  $method
     * @return $this
     */
    public function buildQueryUsingWhere(array $column, $method = 'where')
    {
        $this->query = $this->getQuery()->whereNested(function ($query) use ($column, $method) {
            foreach ($column as $key => $value) {
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
     * Build the query using all "where" clauses that are used in relationships.
     *
     * @param  array  $whereHas
     * @param  array  $whereDoesntHave
     * @param  array  $whereRelation
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function buildQueryUsingAllWheres(
        $whereHas = [],
        $whereDoesntHave = [],
        $whereRelation = [],
        $query = null,
        $method = 'where'
    ) {
        $this->checkQueryExistence($query);

        if ($method === 'orWhere' && empty($this->query)) {
            throw new InvalidQuery;
        }

        $methodPrefix = ($method === 'where' ? '' : 'orWhere');

        if (!empty($whereHas)) {
            $this->buildQueryUsingWhereConditions($whereHas, $query, "{$methodPrefix}Has");
        }

        if (!empty($whereDoesntHave)) {
            $this->buildQueryUsingWhereConditions($whereDoesntHave, $query, "{$methodPrefix}DoesntHave");
        }

        if (!empty($whereRelation)) {
            $this->buildQueryUsingWhereRelations($whereRelation, $query, "{$methodPrefix}Relation");
        }

        return $this;
    }

    /**
     * Build the query using "whereHas", and "whereDoesntHave" queries.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function buildQueryUsingWhereConditions($wheres, $query = null, $method = 'whereHas')
    {
        $this->checkQueryExistence($query);

        if (in_array($method, ['orWhereHas', 'orWhereDoesntHave'], true) && empty($this->query)) {
            throw new InvalidQuery;
        }

        foreach ($wheres as $relation => $closure) {
            if (!is_string($closure) && !is_callable($closure)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            $paramters = $this->prepareWhereConditionsParamters(is_string($closure) ? $closure : $relation, $method);

            $this->query = $this->buildQueryUsingHas(
                $paramters['relation'],
                $paramters['operator'],
                $paramters['count'],
                str_starts_with($method, 'orWhere') ? 'or' : 'and',
                is_callable($closure) ? $closure : null
            );
        }

        return $this;
    }

    /**
     * Build the query using "whereRelation" queries.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function buildQueryUsingWhereRelations($wheres, $query = null, $method = 'whereRelation')
    {
        $this->checkQueryExistence($query);

        if ($method === 'orWhereRelation' && empty($this->query)) {
            throw new InvalidQuery;
        }

        foreach ($wheres as $relation => $closure) {
            if ((!is_string($relation) && !is_callable($closure)) && !is_array($closure)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            $paramters = $this->prepareWhereRelationsParamters($relation, $closure);

            $this->query = $this->buildQueryUsingHas(
                $paramters['relation'],
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQueryUsingHas($relation, $operator = '>=', $count = 1, $boolean = 'and', $closure = null)
    {
        return $this
            ->getQuery()
            ->has(
                $relation,
                $operator,
                $count,
                $boolean,
                $closure
            );
    }

    /**
     * Prepare the where conditions parameters.
     *
     * @param  string  $subject
     * @param  string  $method
     * @return array
     */
    protected function prepareWhereConditionsParamters(string $subject, string $method)
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
            'operator' => array_key_exists(0, $operator) ? $operator[0] : '=',
            'count'    => array_key_exists(1, $matches) ? $matches[1] : 1,
        ];
    }

    /**
     * Prepare the where relations parameters.
     *
     * @param  string  $relation
     * @param  array|\Closure  $closure
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareWhereRelationsParamters(string $relation, array|\Closure $closure)
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
