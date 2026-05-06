<?php

namespace Ramadan\EasyModel\Exceptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class InvalidModel extends LogicException implements EasyModelException
{
    /**
     * The given value is neither a model class string nor a Model instance.
     *
     * @param  mixed  $given
     * @return static
     */
    public static function notAModelClass($given)
    {
        return new static(sprintf(
            'The model must be a string or an instance of [%s]. Given [%s].',
            Model::class,
            get_debug_type($given)
        ));
    }

    /**
     * The trait could not figure out which model to operate on.
     *
     * @return static
     */
    public static function unresolvable()
    {
        return new static('Cannot resolve the searchable model.');
    }

    /**
     * A relationship was provided but the model is anonymous (no primary key).
     *
     * @return static
     */
    public static function cannotSearchAnonymousRelation()
    {
        return new static('Cannot search in a relationship with an anonymous model.');
    }

    /**
     * The model does not use the SoftDeletes trait.
     *
     * @param  string  $class
     * @return static
     */
    public static function softDeletesNotUsed($class)
    {
        return new static(sprintf(
            'The model [%s] does not use the [%s] trait; cannot include soft-deleted records.',
            $class,
            SoftDeletes::class
        ));
    }

    /**
     * No updatable model has been set yet.
     *
     * @return static
     */
    public static function updatableNotSet()
    {
        return new static('You must set the updatable model first.');
    }
}
