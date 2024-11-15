<?php

namespace Ramadan\EasyModel\Exceptions;

use Exception;

class InvalidModel extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
