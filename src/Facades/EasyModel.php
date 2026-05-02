<?php

namespace Ramadan\EasyModel\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Ramadan\EasyModel\EasyModel as EasyModelInstance;

/**
 * @method static EasyModelInstance for(Model|string $model)
 *
 * @see \Ramadan\EasyModel\EasyModel
 */
class EasyModel extends Facade
{
    protected static function getFacadeAccessor()
    {
        return EasyModelInstance::class;
    }
}
