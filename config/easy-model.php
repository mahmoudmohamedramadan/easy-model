<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Order Direction
    |--------------------------------------------------------------------------
    |
    | The default direction used when none is supplied to addOrderBy().
    |
    */

    'default_order_direction' => 'asc',

    /*
    |--------------------------------------------------------------------------
    | Builder Macros
    |--------------------------------------------------------------------------
    |
    | When enabled the package will register helper macros on the Eloquent
    | builder (keywordSearch, orderByRelation, orderByAggregate, etc.) so you
    | can call them directly on Model::query() without using the Searchable
    | trait inside your controller / service.
    |
    */

    'register_builder_macros' => true,

    /*
    |--------------------------------------------------------------------------
    | Operators Pattern
    |--------------------------------------------------------------------------
    |
    | The regular expression pattern used to detect relational operators
    | embedded in the relationship name passed to addWhereHas() — for example
    | "posts>2".
    |
    */

    'operators_pattern' => '/[><=]+/',

];
