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

            $paramters = $this->prepareParamtersForOrderBy($order, $queryBuilder);

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
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareParamtersForOrderBy(string|array $order, $queryBuilder)
    {
        // If the given string does not contain a dot, the order will not be executed
        // using the model relationships.
        // Otherwise, it means the order should be executed on the model relationships therefore, we need
        // to split the order to get the relationships and the column that needs to be ordered.
        if (is_string($order)) {
            $parts = explode('.', $order);

            $column    = $order;
            $direction = 'asc';
        } elseif (is_array($order)) {
            $key   = array_key_first($order);
            $parts = explode('.', $key);

            $column    = $key;
            $direction = strtolower(array_values($order)[0]);
        }

        if (in_array(strtolower($column), ['asc', 'desc'], true)) {
            throw new InvalidArrayStructure('Provide correct orderable column.');
        }

        if (count($parts) > 1) {
            // In case the order is related to the model relationships, we need to get
            // the relationships and the column that needs to be ordered (e.g., "posts.created_at").
            $column = implode('.', array_slice($parts, -2));

            $this->performJoinsForOrderByRelationships($parts, $queryBuilder);
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
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @return void
     */
    protected function performJoinsForOrderByRelationships($relationships, $queryBuilder)
    {
        // Let's pretend that the model will get back an instance of "App\Models\User".
        $currentModel = $this->getModel();

        for ($i = 0; $i < count($relationships) - 1; $i++) {
            // This will call the model relationships (e.g., $user->posts(), $user->comments()).
            $relatedModel = $currentModel->{$relationships[$i]}()->getModel();

            // Get the table name of the current and related models.
            $currentTableName = $currentModel->getTable();
            $relatedTableName = $relatedModel->getTable();

            // Get the foreign key and primary key of the current model.
            $currentForeignKey      = $currentModel->getForeignKey();
            $currentTablePrimaryKey = $currentModel->getKeyName();

            // Perform the join:
            // - Current table is the current model's table (for first iteration, it's the "users" table).
            // - Related table is the related model's table (e.g., "posts").
            $queryBuilder->join(
                table: $relatedTableName,
                first: "{$currentTableName}.{$currentTablePrimaryKey}",
                operator: '=',
                second: "{$relatedTableName}.{$currentForeignKey}"
            );

            // Now, let's move to the next model (the current one will be the related model) hence, if
            // the current model was "users" then, the current model in the next iteration will be "posts".
            $currentModel = $relatedModel;
        }
    }
}
