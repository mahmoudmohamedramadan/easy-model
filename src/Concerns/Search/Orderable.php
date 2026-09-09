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

        $pathSoFar    = $currentModel->getTable();
        $currentAlias = $currentModel->getTable();

        for ($i = 0, $last = count($relationships) - 1; $i < $last; $i++) {
            $relationName = $relationships[$i];

            if (! method_exists($currentModel, $relationName)) {
                throw InvalidOrderableRelationship::relationNotDefined($currentModel, $relationName);
            }

            $currentRelationship = $currentModel->{$relationName}();
            $relatedModel        = $currentRelationship->getRelated();
            $pathSoFar           = "{$pathSoFar}.{$relationName}";

            if (isset($this->joinedRelationshipTables[$pathSoFar])) {
                $currentAlias = $this->joinedRelationshipTables[$pathSoFar];
                $currentModel = $relatedModel;
                continue;
            }

            $currentAlias = $this->joinRelationship(
                $queryBuilder,
                $currentModel,
                $currentRelationship,
                $currentAlias,
                $pathSoFar
            );

            $currentModel = $relatedModel;
        }

        // The "$currentAlias" always points at the latest relationship
        // that you need to use for performing the order.
        return "{$currentAlias}." . end($relationships);
    }

    /**
     * Append the appropriate join(s) to the query builder for a single relationship hop.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function joinRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path)
    {
        return match (true) {
            $relation instanceof MorphTo => $this->joinMorphToRelationship(),
            $relation instanceof MorphToMany => $this->joinMorphToManyRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path),
            $relation instanceof BelongsToMany => $this->joinBelongsToManyRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path),
            $relation instanceof BelongsTo => $this->joinBelongsToRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path),
            $relation instanceof HasManyThrough => $this->joinHasManyThroughRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path),
            $relation instanceof MorphOneOrMany => $this->joinMorphOneOrManyRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path),
            $relation instanceof HasOneOrMany => $this->joinHasOneOrManyRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path),
            default => throw InvalidOrderableRelationship::unsupportedRelation($relation),
        };
    }

    /**
     * Resolve a unique table alias for the given join path.
     *
     * Reuses the existing alias when the same relationship path is joined twice.
     * Otherwise suffixes the table name (`users_2`, `users_3`, ...) whenever the
     * table is already in the FROM/JOIN list — including the searchable model's
     * own table, so `author` and `editor` (both `users`) can coexist.
     *
     * @param  string  $table
     * @param  string  $path
     * @return string
     */
    protected function makeJoinAlias($table, $path)
    {
        if (isset($this->joinedRelationshipTables[$path])) {
            return $this->joinedRelationshipTables[$path];
        }

        $used = array_values($this->joinedRelationshipTables);
        $base = $this->resolveModelOrRelation()->getTable();

        if (! in_array($base, $used, true)) {
            $used[] = $base;
        }

        $alias  = $table;
        $suffix = 2;

        while (in_array($alias, $used, true)) {
            $alias = "{$table}_{$suffix}";
            $suffix++;
        }

        $this->joinedRelationshipTables[$path] = $alias;

        return $alias;
    }

    /**
     * Build a JOIN table expression, aliasing only when the alias differs from the table name.
     *
     * @param  string  $table
     * @param  string  $alias
     * @return string
     */
    protected function joinTableName($table, $alias)
    {
        return $alias === $table ? $table : "{$table} as {$alias}";
    }

    /**
     * MorphTo cannot be joined: the related table is not statically known.
     *
     * @return never
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidOrderableRelationship
     */
    protected function joinMorphToRelationship()
    {
        throw InvalidOrderableRelationship::morphToCannotBeJoined();
    }

    /**
     * Join a BelongsToMany (non-polymorphic) relation via the pivot table.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     */
    protected function joinBelongsToManyRelationship($queryBuilder, $parentModel, BelongsToMany $relation, $parentAlias, $path)
    {
        $relatedTable = $relation->getRelated()->getTable();
        $pivotTable   = $relation->getTable();
        $pivotAlias   = $this->makeJoinAlias($pivotTable, $path . '._pivot');
        $relatedAlias = $this->makeJoinAlias($relatedTable, $path);

        $queryBuilder->leftJoin(
            $this->joinTableName($pivotTable, $pivotAlias),
            "{$parentAlias}.{$relation->getParentKeyName()}",
            '=',
            "{$pivotAlias}.{$relation->getForeignPivotKeyName()}"
        );

        $queryBuilder->leftJoin(
            $this->joinTableName($relatedTable, $relatedAlias),
            "{$pivotAlias}.{$relation->getRelatedPivotKeyName()}",
            '=',
            "{$relatedAlias}.{$relation->getRelatedKeyName()}"
        );

        return $relatedAlias;
    }

    /**
     * Join a MorphToMany relation via the pivot table and morph type constraint.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\MorphToMany  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     */
    protected function joinMorphToManyRelationship($queryBuilder, $parentModel, MorphToMany $relation, $parentAlias, $path)
    {
        $relatedAlias = $this->joinBelongsToManyRelationship($queryBuilder, $parentModel, $relation, $parentAlias, $path);
        $pivotAlias   = $this->joinedRelationshipTables[$path . '._pivot'];

        $queryBuilder->where(
            "{$pivotAlias}.{$relation->getMorphType()}",
            '=',
            $relation->getMorphClass()
        );

        return $relatedAlias;
    }

    /**
     * Join a BelongsTo relation.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsTo  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     */
    protected function joinBelongsToRelationship($queryBuilder, $parentModel, BelongsTo $relation, $parentAlias, $path)
    {
        $relatedTable = $relation->getRelated()->getTable();
        $relatedAlias = $this->makeJoinAlias($relatedTable, $path);

        $queryBuilder->leftJoin(
            $this->joinTableName($relatedTable, $relatedAlias),
            "{$parentAlias}.{$relation->getForeignKeyName()}",
            '=',
            "{$relatedAlias}.{$relation->getOwnerKeyName()}"
        );

        return $relatedAlias;
    }

    /**
     * Join HasOneThrough / HasManyThrough via the intermediate model.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\HasManyThrough  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     */
    protected function joinHasManyThroughRelationship($queryBuilder, $parentModel, HasManyThrough $relation, $parentAlias, $path)
    {
        $relatedTable  = $relation->getRelated()->getTable();
        $throughTable  = $relation->getParent()->getTable();
        $throughPath   = $path . '._through';
        $alreadyJoined = isset($this->joinedRelationshipTables[$throughPath]);
        $throughAlias  = $this->makeJoinAlias($throughTable, $throughPath);
        $relatedAlias  = $this->makeJoinAlias($relatedTable, $path);

        if (! $alreadyJoined) {
            $queryBuilder->leftJoin(
                $this->joinTableName($throughTable, $throughAlias),
                "{$parentAlias}.{$relation->getLocalKeyName()}",
                '=',
                "{$throughAlias}.{$relation->getFirstKeyName()}"
            );
        }

        $queryBuilder->leftJoin(
            $this->joinTableName($relatedTable, $relatedAlias),
            "{$throughAlias}.{$relation->getSecondLocalKeyName()}",
            '=',
            "{$relatedAlias}.{$relation->getForeignKeyName()}"
        );

        return $relatedAlias;
    }

    /**
     * Join MorphOne / MorphMany with a morph type filter on the related table.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\MorphOneOrMany  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     */
    protected function joinMorphOneOrManyRelationship($queryBuilder, $parentModel, MorphOneOrMany $relation, $parentAlias, $path)
    {
        $relatedTable = $relation->getRelated()->getTable();
        $relatedAlias = $this->makeJoinAlias($relatedTable, $path);

        $queryBuilder->leftJoin($this->joinTableName($relatedTable, $relatedAlias), function ($join) use ($parentAlias, $relation, $relatedAlias) {
            $join
                ->on(
                    "{$parentAlias}.{$relation->getLocalKeyName()}",
                    '=',
                    "{$relatedAlias}.{$relation->getForeignKeyName()}"
                )
                ->where(
                    "{$relatedAlias}.{$relation->getMorphType()}",
                    '=',
                    $relation->getMorphClass()
                );
        });

        return $relatedAlias;
    }

    /**
     * Join HasOne / HasMany from the parent's local key to the related foreign key.
     *
     * @param  \Illuminate\Database\Query\Builder  $queryBuilder
     * @param  \Illuminate\Database\Eloquent\Model  $parentModel
     * @param  \Illuminate\Database\Eloquent\Relations\HasOneOrMany  $relation
     * @param  string  $parentAlias
     * @param  string  $path
     * @return string
     */
    protected function joinHasOneOrManyRelationship($queryBuilder, $parentModel, HasOneOrMany $relation, $parentAlias, $path)
    {
        $relatedTable = $relation->getRelated()->getTable();
        $relatedAlias = $this->makeJoinAlias($relatedTable, $path);

        $queryBuilder->leftJoin(
            $this->joinTableName($relatedTable, $relatedAlias),
            "{$parentAlias}.{$relation->getLocalKeyName()}",
            '=',
            "{$relatedAlias}.{$relation->getForeignKeyName()}"
        );

        return $relatedAlias;
    }
}
