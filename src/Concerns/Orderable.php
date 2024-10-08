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
        // If the given string does not contain a dot, this means that the order
        // is not related to the model relationships.
        // Otherwise, it means the order is related to the model relationships therefore, we need
        // to split the order to get the relationships and the column that needs to be ordered.
        if (is_string($order) && !str_contains($order, '.')) {
            $column    = $order;
            $direction = 'asc';
        } elseif (is_string($order) && str_contains($order, '.')) {
            $relationships = explode('.', $order);

            $column    = end($relationships);
            $direction = 'asc';

            $this->performJoinsForOrderByRelationships($relationships);
        } elseif (is_array($order) && !str_contains(array_key_first($order), '.')) {
            $column    = end($order);
            $direction = strtolower(array_values($order)[0]);
        } elseif (is_array($order) && str_contains(array_key_first($order), '.')) {
            $relationships = explode('.', array_key_first($order));

            $column    = end($relationships);
            $direction = strtolower(array_values($order)[0]);

            $this->performJoinsForOrderByRelationships($relationships);
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArrayStructure('Order direction must be "asc" or "desc".');
        }

        return [
            'column'    => $column,
            'direction' => $direction
        ];
    }

    /**
     * Perform the "orderBy" joins.
     *
     * @param  array  $relationships
     * @return void
     */
    protected function performJoinsForOrderByRelationships($relationships)
    {
        $model        = $this->getModel();
        $queryBuilder = $model->newQuery();

        // Keep track of the previous model's table to set the join condition
        $previousTable = $model->getTable();

        for ($i = 0; $i < count($relationships) - 1; $i++) {
            // This will call the model relationships (e.g., posts(), comments())
            $relatedModel = $model->{$relationships[$i]}()->getModel();

            // Get the table name of the related model
            $relatedTable = $relatedModel->getTable();
            $foreignKey   = $relatedModel->getForeignKey();

            // Define the current table's primary key
            $primaryKey = $model->getKeyName();

            // Perform the join:
            // - Previous table is the current model's table (for first iteration, it's the User table)
            // - Current table is the related model's table (e.g. posts, comments, etc.)
            $queryBuilder->join($relatedTable, "{$previousTable}.{$primaryKey}", '=', "{$relatedTable}.{$foreignKey}");

            // Move to the next model (the related model now becomes the "current" model)
            // then update the table for the next iteration
            $model         = $relatedModel;
            $previousTable = $relatedTable;
        }
    }
}
