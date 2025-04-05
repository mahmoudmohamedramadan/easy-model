<?php

namespace Ramadan\EasyModel\Concerns\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;
use Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship;

trait Orderable
{
    /**
     * Add an "order by" clause to the query.
     *
     * @param  array  $orders
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    public function addOrderBy(array $orders, ?Builder $query = null)
    {
        $queryBuilder = $this->getSearchableQueryBuilder($query);
        foreach ($orders as $order) {
            if (!is_string($order) && !is_array($order)) {
                throw new InvalidArrayStructure(sprintf("The [%s] method must be well defined.", __METHOD__));
            }

            $paramters = $this->prepareOrderByQueryParamters($order, $queryBuilder);

            $queryBuilder->{$queryBuilder->unions ? 'unionOrders' : 'orders'}[] = [
                'column'    => $paramters['column'],
                'direction' => $paramters['direction'],
            ];
        }
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Prepare the "order by" parameters.
     *
     * @param  string|array  $order
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function prepareOrderByQueryParamters($order, $queryBuilder)
    {
        $currentModel = $this->resolveModelOrRelation();

        // If the given string does not contain a dot, the order will be applied directly to the
        // model's column. However, if the string includes a dot, it indicates that the order should
        // be applied to a related model. In this case, we need to split the string to separate the
        // relationship and the column by which the model should be ordered.
        if (is_string($order)) {
            $parts = explode('.', $order);

            // If the developer attempts to order by the same column from both the model and its relationship,
            // an "Ambiguous Exception" will occur. To prevent this, we explicitly use the searchable model's
            // table for ordering by its column.
            // Example usage of "addOrderBy" method:
            // ->setSearchableModel(User::class)
            // ->addOrderBy([
            //     ['created_at' => 'desc'], // This will trigger an "order by" on the "users" table
            //     'posts.created_at' // You can also specify which relationship's table to use for ordering by its column
            // ])
            $column    = "{$currentModel->getTable()}.{$order}";
            $direction = 'asc';
        } elseif (is_array($order)) {
            $key   = array_key_first($order);
            $parts = explode('.', $key);

            $column    = "{$currentModel->getTable()}.{$key}";
            $direction = strtolower(array_values($order)[0]);
        }

        if (in_array(strtolower($column), ['asc', 'desc'], true)) {
            throw new InvalidArrayStructure("Provide correct orderable column.");
        }

        if (count($parts) > 1) {
            // In case the order is related to the model relationships, we need to get the last
            // relationship and the column that needs to be ordered (e.g., "post.comments.created_at").
            $column = $this->performJoinsForRelationships($currentModel, $parts, $queryBuilder);
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
     * Perform joins for relationships in the query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $currentModel
     * @param  array  $relationships
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @return string
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function performJoinsForRelationships($currentModel, $relationships, $queryBuilder)
    {
        for ($i = 0; $i < count($relationships) - 1; $i++) {
            $currentRelationship = $currentModel->{$relationships[$i]}();
            $relatedModel        = $currentRelationship->getModel();

            // At first let's pretend that the current model is "App\Models\User" which is the parent and it has
            // one or many child models (e.g., "profile", "accounts") in this case, the foreign key of the current
            // model is "user_id" and must be exists in the child table(s).
            if (in_array(get_class($currentRelationship), [HasOne::class, HasMany::class])) {
                $currentTableName = $currentModel->getTable();
                $relatedTableName = $relatedModel->getTable();

                $currentForeignKey      = $currentModel->getForeignKey();
                $currentTablePrimaryKey = $currentModel->getKeyName();
            }

            // But, in case the current model is "App\Models\Comment" which is the child and it belongs to a
            // parent model (e.g., "App\Models\Post") the foreign key "post_id" must exists in the table of the
            // current related model "comments".
            elseif (in_array(get_class($currentRelationship), [BelongsTo::class, BelongsToMany::class])) {
                $currentTableName = $relatedModel->getTable();
                $relatedTableName = $currentModel->getTable();

                $currentForeignKey      = $relatedModel->getForeignKey();
                $currentTablePrimaryKey = $currentModel->getKeyName();
            }

            if (empty($currentTableName) || empty($relatedTableName)) {
                throw new InvalidOrderableRelationship(
                    sprintf("The orderable relationship [%s] is unsupported.", get_class($currentRelationship))
                );
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

        // The "$currentModel" always contains the latest relationship
        // that you need to use for performing the order.
        return "{$currentModel->getTable()}." . end($relationships);
    }
}
