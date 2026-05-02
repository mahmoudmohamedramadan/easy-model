<?php

namespace Ramadan\EasyModel\Exceptions;

/**
 * Marker interface implemented by every exception thrown by the package.
 *
 * Catch this to handle any EasyModel error in a single block:
 *
 *   try {
 *       EasyModel::for(User::class)->addWheres([...])->execute()->get();
 *   } catch (\Ramadan\EasyModel\Exceptions\EasyModelException $e) {
 *       report($e);
 *   }
 */
interface EasyModelException
{
}
