<?php

namespace Ramadan\EasyModel\Concerns\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;
use Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship;

trait Orderable
{
    /**
     * Tables that have already been joined for the current order-by chain.
     *
     * Tracks aliases by full path (e.g. "users.posts.comments") so the same
     * relationship is never joined twice in a single query.
     *
     * @var array<string, string>
     */
    protected array $joinedRelationshipTables = [];

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
            if (! is_string($order) && ! is_array($order)) {
                throw InvalidArrayStructure::methodMustBeWellDefined(__METHOD__);
            }

            $parameters = $this->prepareOrderByQueryParameters($order, $queryBuilder);

            $queryBuilder->{$queryBuilder->unions ? 'unionOrders' : 'orders'}[] = [
                'column'    => $parameters['column'],
                'direction' => $parameters['direction'],
            ];
        }

        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Add an "order by" clause that orders by the count of a given relationship.
     *
     * @param  string  $relation
     * @param  string  $direction
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrderByCount(string $relation, string $direction = 'asc')
    {
        return $this->addOrderByAggregate($relation, '*', 'count', $direction);
    }

    /**
     * Add an "order by" clause that orders by an aggregate over a relationship column.
     *
     * @param  string  $relation
     * @param  string  $column
     * @param  string  $aggregate
     * @param  string  $direction
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     * @throws \Ramadan\EasyModel\Exceptions\InvalidModel
     */
    public function addOrderByAggregate(string $relation, string $column, string $aggregate, string $direction = 'asc')
    {
        $aggregate = strtolower($aggregate);
        $direction = strtolower($direction);

        if (! in_array($aggregate, ['count', 'sum', 'avg', 'min', 'max'], true)) {
            throw InvalidArrayStructure::invalidAggregate();
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw InvalidArrayStructure::invalidDirection();
        }

        $eloquent = $this->getSearchableEloquentBuilder();

        $eloquent->withAggregate($relation, $column, $aggregate);

        $alias = sprintf(
            '%s_%s%s',
            str_replace('.', '_', $relation),
            $aggregate,
            $aggregate === 'count' && $column === '*' ? '' : '_' . $column
        );

        $eloquent->orderBy($alias, $direction);

        $this->eloquentBuilder = $eloquent;
        $this->queryBuilder    = $eloquent->getQuery();

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
    protected function prepareOrderByQueryParameters($order, $queryBuilder)
    {
        $currentModel = $this->resolveModelOrRelation();

        // If the given string does not contain a dot, the order will be applied directly to the
        // model's column. However, if the string includes a dot, it indicates that the order should
        // be applied to a relationship. In this case, we need to split the string to separate the
        // relationship and the column by which the model should be ordered.
        if (is_string($order)) {
            $parts     = explode('.', $order);
            $column    = "{$currentModel->getTable()}.{$order}";
            $direction = 'asc';
        } else {
            $key       = array_key_first($order);
            $parts     = explode('.', $key);
            $column    = "{$currentModel->getTable()}.{$key}";
            $direction = strtolower(array_values($order)[0]);
        }

        if (count($parts) > 1) {
            // If the order is based on model relationships, we need to retrieve the last relationship
            // and the column to be ordered by (e.g., "post.comments.created_at").
            $column = $this->performRelationshipsJoins($currentModel, $parts, $queryBuilder);
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw InvalidArrayStructure::invalidDirection();
        }

        return [
            'column'    => $column,
            'direction' => $direction,
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
    protected function performRelationshipsJoins($currentModel, $relationships, $queryBuilder)
    {
        // Make sure the parent columns are not duplicated when we join children. We
        // pin the SELECT to the base table once and let downstream code add aliased
        // aggregate columns if needed.
        if (empty($queryBuilder->columns)) {
            $queryBuilder->select("{$currentModel->getTable()}.*");
        }

        $pathSoFar = $currentModel->getTable();

        for ($i = 0, $last = count($relationships) - 1; $i < $last; $i++) {
            $relationName = $relationships[$i];

            if (! method_exists($currentModel, $relationName)) {
                throw InvalidOrderableRelationship::relationNotDefined($currentModel, $relationName);
            }

            $currentRelationship = $currentModel->{$relationName}();
            $relatedModel        = $currentRelationship->getRelated();
            $pathSoFar           = "{$pathSoFar}.{$relationName}";

            if (isset($this->joinedRelationshipTables[$pathSoFar])) {
                $currentModel = $relatedModel;
                continue;
            }

            $this->joinRelationship($queryBuilder, $currentModel, $currentRelationship);

            $this->joinedRelationshipTables[$pathSoFar] = $relatedModel->getTable();

            $currentModel = $relatedModel;
        }

        // The "$currentModel" always contains the latest relationship
        // that you need to use for performing the order.
        return "{$currentModel->getTable()}." . end($relationships);
    }

    /**
     * Append the appropriate join(s) to the query builder for a single relationship hop.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function joinRelationship($queryBuilder, $parentModel, $relation)
    {
        match (true) {
            $relation instanceof MorphTo => $this->joinMorphToRelationship(),
            $relation instanceof MorphToMany => $this->joinMorphToManyRelationship($queryBuilder, $parentModel, $relation),
            $relation instanceof BelongsToMany => $this->joinBelongsToManyRelationship($queryBuilder, $parentModel, $relation),
            $relation instanceof BelongsTo => $this->joinBelongsToRelationship($queryBuilder, $parentModel, $relation),
            $relation instanceof HasManyThrough => $this->joinHasManyThroughRelationship($queryBuilder, $parentModel, $relation),
            $relation instanceof MorphOneOrMany => $this->joinMorphOneOrManyRelationship($queryBuilder, $parentModel, $relation),
            $relation instanceof HasOneOrMany => $this->joinHasOneOrManyRelationship($queryBuilder, $parentModel, $relation),
            default => throw InvalidOrderableRelationship::unsupportedRelation($relation),
        };
    }

    /**
     * MorphTo cannot be joined: the related table is not statically known.
     *
     * @return never
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function joinMorphToRelationship(): void
    {
        throw InvalidOrderableRelationship::morphToCannotBeJoined();
    }

    /**
     * Join a BelongsToMany (non-polymorphic) relation via the pivot table.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @return void
     */
    protected function joinBelongsToManyRelationship($queryBuilder, $parentModel, BelongsToMany $relation): void
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $parentModel->getTable();
        $pivotTable   = $relation->getTable();

        $queryBuilder->leftJoin(
            $pivotTable,
            "{$parentTable}.{$relation->getParentKeyName()}",
            '=',
            "{$pivotTable}.{$relation->getForeignPivotKeyName()}"
        );

        $queryBuilder->leftJoin(
            $relatedTable,
            "{$pivotTable}.{$relation->getRelatedPivotKeyName()}",
            '=',
            "{$relatedTable}.{$relation->getRelatedKeyName()}"
        );
    }

    /**
     * Join a MorphToMany relation via the pivot table and morph type constraint.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @return void
     */
    protected function joinMorphToManyRelationship($queryBuilder, $parentModel, MorphToMany $relation): void
    {
        $this->joinBelongsToManyRelationship($queryBuilder, $parentModel, $relation);

        $pivotTable = $relation->getTable();

        $queryBuilder->where(
            "{$pivotTable}.{$relation->getMorphType()}",
            '=',
            $relation->getMorphClass()
        );
    }

    /**
     * Join a BelongsTo relation.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @return void
     */
    protected function joinBelongsToRelationship($queryBuilder, $parentModel, BelongsTo $relation): void
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $parentModel->getTable();

        $queryBuilder->leftJoin(
            $relatedTable,
            "{$parentTable}.{$relation->getForeignKeyName()}",
            '=',
            "{$relatedTable}.{$relation->getOwnerKeyName()}"
        );
    }

    /**
     * Join HasOneThrough / HasManyThrough via the intermediate model.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @return void
     */
    protected function joinHasManyThroughRelationship($queryBuilder, $parentModel, HasManyThrough $relation): void
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $parentModel->getTable();
        $through      = $relation->getParent();
        $throughTable = $through->getTable();

        if (! isset($this->joinedRelationshipTables['__through:' . $throughTable])) {
            $queryBuilder->leftJoin(
                $throughTable,
                "{$parentTable}.{$relation->getLocalKeyName()}",
                '=',
                "{$throughTable}.{$relation->getFirstKeyName()}"
            );

            $this->joinedRelationshipTables['__through:' . $throughTable] = $throughTable;
        }

        $queryBuilder->leftJoin(
            $relatedTable,
            "{$throughTable}.{$relation->getSecondLocalKeyName()}",
            '=',
            "{$relatedTable}.{$relation->getForeignKeyName()}"
        );
    }

    /**
     * Join MorphOne / MorphMany with a morph type filter on the related table.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @return void
     */
    protected function joinMorphOneOrManyRelationship($queryBuilder, $parentModel, MorphOneOrMany $relation): void
    {
        $relatedTable = $relation->getRelated()->getTable();

        $queryBuilder->leftJoin($relatedTable, function ($join) use ($parentModel, $relation, $relatedTable) {
            $join
                ->on(
                    "{$parentModel->getTable()}.{$relation->getLocalKeyName()}",
                    '=',
                    "{$relatedTable}.{$relation->getForeignKeyName()}"
                )
                ->where(
                    "{$relatedTable}.{$relation->getMorphType()}",
                    '=',
                    $relation->getMorphClass()
                );
        });
    }

    /**
     * Join HasOne / HasMany from the parent's local key to the related foreign key.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @return void
     */
    protected function joinHasOneOrManyRelationship($queryBuilder, $parentModel, HasOneOrMany $relation): void
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $parentModel->getTable();

        $queryBuilder->leftJoin(
            $relatedTable,
            "{$parentTable}.{$relation->getLocalKeyName()}",
            '=',
            "{$relatedTable}.{$relation->getForeignKeyName()}"
        );
    }
}
