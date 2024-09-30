<?php

namespace Ramadan\EasyModel\Exceptions;

use Exception;

class InvalidQuery extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'No query has been set.')
    {
        parent::__construct($message);
    }
}
