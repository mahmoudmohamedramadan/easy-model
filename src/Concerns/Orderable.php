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
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrderBy(array $orders, Builder $query = null)
    {
        $queryBuilder = $this->getQueryBuilder($query);
        foreach ($orders as $order) {
            if (!is_string($order) && !is_array($order)) {
                throw new InvalidArrayStructure("The `orderBy` array must be well defined.");
            }

            $paramters = $this->prepareParamtersForOrderBy($order);

            $queryBuilder->{$queryBuilder->unions ? 'unionOrders' : 'orders'}[] = [
                'column'    => $paramters['column'],
                'direction' => $paramters['direction'],
            ];
        }
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Prepare the "orderBy" parameters.
     *
     * @param  string|array  $order
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareParamtersForOrderBy(string|array $order)
    {
        if (is_string($order)) {
            return [
                'column'    => $order,
                'direction' => 'asc'
            ];
        }

        $direction = array_values($order)[0];

        if (!in_array(strtolower($direction), ['asc', 'desc'], true)) {
            throw new InvalidArrayStructure('Order direction must be "asc" or "desc".');
        }

        return [
            'column'    => array_key_first($order),
            'direction' => $direction,
        ];
    }
}
