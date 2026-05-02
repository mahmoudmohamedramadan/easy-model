<?php

namespace Ramadan\EasyModel\Exceptions;

use InvalidArgumentException;

class InvalidArrayStructure extends InvalidArgumentException implements EasyModelException
{
    /**
     * Generic "method got malformed input" message.
     *
     * @param  string  $method
     * @return static
     */
    public static function methodMustBeWellDefined($method)
    {
        return new static(sprintf('The [%s] method must be well defined.', $method));
    }

    /**
     * The user passed something that is neither an array nor a Closure.
     *
     * @param  string  $method
     * @param  mixed  $given
     * @return static
     */
    public static function invalidWhereEntry($method, $given)
    {
        return new static(sprintf(
            'Each entry passed to [%s] must be an array or a Closure. Given [%s].',
            $method,
            get_debug_type($given)
        ));
    }

    /**
     * The user passed an array tuple of the wrong length to a where helper.
     *
     * @param  string  $method
     * @param  int  $count
     * @return static
     */
    public static function invalidWhereTuple($method, $count)
    {
        return new static(sprintf(
            'Each entry passed to [%s] must be an array of [column, value] or [column, operator, value]. Got %d element(s).',
            $method,
            $count
        ));
    }

    /**
     * The user passed an array tuple of the wrong shape to whereIn / whereBetween / etc.
     *
     * @param  string  $method
     * @return static
     */
    public static function invalidColumnValuesTuple($method)
    {
        return new static(sprintf(
            'Each entry passed to [%s] must be a [column, values] pair.',
            $method
        ));
    }

    /**
     * The user passed an unsupported aggregate function name.
     *
     * @return static
     */
    public static function invalidAggregate()
    {
        return new static('Aggregate must be one of: count, sum, avg, min, max.');
    }

    /**
     * The user passed an unsupported order direction.
     *
     * @return static
     */
    public static function invalidDirection()
    {
        return new static('Order direction must be "asc" or "desc".');
    }
}
