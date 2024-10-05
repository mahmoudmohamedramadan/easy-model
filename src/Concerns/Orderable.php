<?php

namespace Ramadan\EasyModel\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;

trait Orderable
{
    /**
     * Add an "order by" clause to the query.
     *
     * @param  array  $orders
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrderBy(array $orders, Builder $query = null)
    {
        $this->checkQueryExistence($query);

        foreach ($orders as $column => $direction) {
            if ((!is_string($column) || !is_numeric($column)) && !is_string($direction)) {
                throw new InvalidArrayStructure("The `orderBy` array must be well defined.");
            }

            $paramters = $this->prepareParamtersForOrderBy($column, $direction);

            $this->query = $this->getQuery()->orderBy($paramters['column'], $paramters['direction']);
        }

        return $this;
    }

    /**
     * Prepare the "orderBy" parameters.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareParamtersForOrderBy(string $column, string $direction)
    {
        if (is_numeric($column) && is_string($direction)) {
            return [
                'column'    => $direction,
                'direction' => 'asc'
            ];
        }

        if (!in_array(strtolower($direction), ['asc', 'desc'], true)) {
            throw new InvalidArrayStructure("The `orderBy` array must be well defined.");
        }

        return [
            'column'    => $column,
            'direction' => $direction,
        ];
    }
}
