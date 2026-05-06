<?php

namespace Ramadan\EasyModel\Exceptions;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class InvalidOrderableRelationship extends LogicException implements EasyModelException
{
    /**
     * The relationship type cannot be turned into a SQL join.
     *
     * @param  object  $relation
     * @return static
     */
    public static function unsupportedRelation($relation)
    {
        return new static(sprintf(
            'The orderable relationship [%s] is unsupported.',
            is_object($relation) ? get_class($relation) : (string) $relation
        ));
    }

    /**
     * MorphTo relations are dynamically polymorphic and cannot be joined.
     *
     * @return static
     */
    public static function morphToCannotBeJoined()
    {
        return new static(sprintf(
            'Cannot order by a [%s] relationship; the related table is polymorphic and only known at runtime.',
            MorphTo::class
        ));
    }

    /**
     * The given model has no method matching the requested relationship name.
     *
     * @param  object|string  $model
     * @param  string  $relation
     * @return static
     */
    public static function relationNotDefined($model, $relation)
    {
        return new static(sprintf(
            'The model [%s] does not define a [%s] relationship.',
            is_object($model) ? get_class($model) : (string) $model,
            $relation
        ));
    }
}
