<?php

namespace Ramadan\EasyModel\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
            $column = $this->performJoinsForOrderByRelationships($parts, $queryBuilder);
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
     * @return string
     */
    protected function performJoinsForOrderByRelationships($relationships, $queryBuilder)
    {
        $currentModel = $this->resolveModelOrRelation();

        for ($i = 0; $i < count($relationships) - 1; $i++) {
            $currentRelationship = $currentModel->{$relationships[$i]}();
            $relatedModel        = $currentRelationship->getModel();

            // At first let's pretend that the current model is "App\Models\User" which is the parent and he has
            // one or many child models (e.g., "post", "comments") in this case, the foreign key of the current
            // model is "user_id" and must be exists in the child table(s).
            if (in_array(get_class($currentRelationship), [HasMany::class, HasOne::class])) {
                $currentTableName = $currentModel->getTable();
                $relatedTableName = $relatedModel->getTable();

                $currentForeignKey      = $currentModel->getForeignKey();
                $currentTablePrimaryKey = $currentModel->getKeyName();
            }

            // But, in case the current model is "App\Models\Post" which is the child and it belongs to
            // a parent model (e.g., "user") the foreign key "user_id" must exists in the table of the
            // current related model "posts".
            elseif (in_array(get_class($currentRelationship), [BelongsTo::class, BelongsToMany::class])) {
                $currentTableName = $relatedModel->getTable();
                $relatedTableName = $currentModel->getTable();

                $currentForeignKey      = $relatedModel->getForeignKey();
                $currentTablePrimaryKey = $currentModel->getKeyName();
            }

            // Perform the join
            $queryBuilder->join(
                table: $relatedModel->getTable(),
                first: "{$currentTableName}.{$currentTablePrimaryKey}",
                operator: '=',
                second: "{$relatedTableName}.{$currentForeignKey}"
            );

            // Now, let's move to the next model (the current one will be the related model). If the current
            // model is "users," then the current model in the next iteration will be "posts".
            $currentModel = $relatedModel;
        }

        // The "$currentModel" always contains the latest relationship that you need to use for performing the order
        return "{$currentModel->getTable()}.{$relationships[count($relationships) - 1]}";
    }
}
