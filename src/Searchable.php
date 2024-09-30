<?php

namespace Ramadan\EasyModel\Eloquent;

use Ramadan\EasyModel\Exceptions\InvalidArrayStructure;
use Ramadan\EasyModel\Exceptions\InvalidQuery;
use Ramadan\EasyModel\Exceptions\InvalidSearchableModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * The model to search in.
     *
     * @var string
     */
    protected $model;

    /**
     * The search query.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * The allowed operators.
     *
     * @var array
     */
    protected $allowedOperators =  [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'like',
        'not like',
        'ilike',
        'not ilike',
        'rlike',
        'not rlike',
    ];

    /**
     * The operators pattern.
     *
     * @var string
     */
    protected $operatorsPattern = '/[><=]+/';

    /**
     * Get the allowed operators.
     *
     * @return array
     */
    public function getAllowedOperators()
    {
        return $this->allowedOperators;
    }

    /**
     * Set the model without chaining the query.
     *
     * @param  string  $model
     * @return void
     */
    public function setModel(string $model)
    {
        $this->model = $model;
    }

    /**
     * Get the current model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the model then chain the query.
     *
     * @param  string  $model
     * @return $this
     */
    public function setChainableModel(string $model)
    {
        $this->setModel($model);

        return $this;
    }

    /**
     * Add the "where" clauses to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $q
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWheres(array $wheres, Builder $query = null)
    {
        return $this->buildQueryUsingWheres($wheres, $query);
    }

    /**
     * Add the "or where" clauses to the query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWheres(array $wheres, Builder $query = null)
    {
        return $this->buildQueryUsingWheres($wheres, $query, 'orWhere');
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereHas(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query);

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereHas(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query, 'orWhereHas');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query, 'whereDoesntHave');

        return $this;
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereDoesntHave(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereConditions($wheres, $query, 'orWhereDoesntHave');

        return $this;
    }

    /**
     * Add a basic where clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addWhereRelation(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereRelations($wheres, $query, 'whereRelation');

        return $this;
    }

    /**
     * Add an "or where" clause to a relationship query.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    public function addOrWhereRelation(array $wheres, Builder $query = null)
    {
        $this->buildQueryUsingWhereRelations($wheres, $query, 'orWhereRelation');

        return $this;
    }

    /**
     * Try to guess the model if not provided.
     *
     * @return $this
     */
    protected function guessModel()
    {
        // This trait may be used in the Model that you need to search in so, we will
        // guess the model using the Model name in case it is not provided.
        if (!empty($this->getModel())) {
            return $this;
        }

        if (is_a(self::class, Model::class, true)) {
            $this->model = self::class;
        }

        return $this;
    }

    /**
     * Check if the query has been set.
     *
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     */
    protected function checkQueryExistence()
    {
        if (empty($this->buildQuery())) {
            throw new InvalidSearchableModel('Provide a model to search in.');
        }

        return $this;
    }

    /**
     * Build the query using "where" clauses.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function buildQueryUsingWheres($wheres, $query = null, string $method = 'where')
    {
        if (!empty($query)) {
            $this->query = $query;
        }

        $this
            ->guessModel()
            ->checkQueryExistence();

        if ($method === 'orWhere' && empty($this->query)) {
            throw new InvalidQuery;
        }

        $this->query = $this->buildQuery()->where(function ($q) use ($wheres, $method) {
            $this->prepareBunchOfWhereClauses($q, $wheres, $method);
        }, boolean: $method === 'where' ? 'and' : 'or');

        return $this;
    }

    /**
     * Build the query using "whereHas", and "whereDoesntHave" queries.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function buildQueryUsingWhereConditions(array $wheres, $query = null, string $method = 'whereHas')
    {
        if (!empty($query)) {
            $this->query = $query;
        }

        $this
            ->guessModel()
            ->checkQueryExistence();

        if (in_array($method, ['orWhereHas', 'orWhereDoesntHave'], true) && empty($this->query)) {
            throw new InvalidQuery;
        }

        foreach ($wheres as $relation => $closure) {
            if (!is_string($closure) && !is_callable($closure)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            $paramters = $this->prepareWhereConditionsParamters(is_string($closure) ? $closure : $relation, $method);

            $this->query = $this->buildQueryUsingHas(
                $paramters['relation'],
                $paramters['operator'],
                $paramters['count'],
                str_starts_with($method, 'orWhere') ? 'or' : 'and',
                is_callable($closure) ? $closure : null
            );
        }

        return $this;
    }

    /**
     * Build the query using "whereRelation" queries.
     *
     * @param  array  $wheres
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @param  string  $method
     * @return $this
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidSearchableModel
     * @throws \Ramadan\EasyModel\Exceptions\InvalidQuery
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function buildQueryUsingWhereRelations(array $wheres, $query = null, string $method = 'whereRelation')
    {
        if (!empty($query)) {
            $this->query = $query;
        }

        $this
            ->guessModel()
            ->checkQueryExistence();

        if ($method === 'orWhereRelation' && empty($this->query)) {
            throw new InvalidQuery;
        }

        foreach ($wheres as $relation => $closure) {
            if ((!is_string($relation) && !is_callable($closure)) && !is_array($closure)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            $paramters = $this->prepareWhereRelationsParamters($relation, $closure);

            $this->query = $this->buildQueryUsingHas(
                $paramters['relation'],
                boolean: str_starts_with($method, 'orWhere') ? 'or' : 'and',
                closure: function ($query) use ($paramters) {
                    if (is_callable($column = $paramters['column'])) {
                        $column($query);
                    } else {
                        $query->where($paramters['column'], $paramters['operator'], $paramters['value']);
                    }
                }
            );
        }

        return $this;
    }

    /**
     * Prepare the where conditions parameters.
     *
     * @param  string  $subject
     * @param  string  $method
     * @return array
     */
    protected function prepareWhereConditionsParamters(string $subject, string $method)
    {
        if (in_array($method, ['whereDoesntHave', 'orWhereDoesntHave'], true)) {
            return [
                'relation' => $subject,
                'operator' => '<',
                'count'    => 1,
            ];
        }

        preg_match($this->operatorsPattern, $subject, $operator);
        $matches = preg_split($this->operatorsPattern, $subject);

        return [
            'relation' => $matches[0],
            'operator' => array_key_exists(0, $operator) ? $operator[0] : '=',
            'count'    => array_key_exists(1, $matches) ? $matches[1] : 1,
        ];
    }

    /**
     * Prepare the where relations parameters.
     *
     * @param  string  $relation
     * @param  array  $closure
     * @return array
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareWhereRelationsParamters(string $relation, array $closure)
    {
        if (is_string($relation) && is_callable($closure)) {
            return [
                'relation' => $relation,
                'column'   => $closure,
                'operator' => '>=',
                'value'    => 1
            ];
        }

        $relation = $closure[0];
        $column   = $closure[1];

        if (count($closure) === 3) {
            $operator = '=';
            $value    = $closure[2];
        } elseif (count($closure) === 4) {
            $operator = $closure[2];
            $value    = $closure[3];
        }

        if (!in_array(strtolower($operator), $this->getAllowedOperators(), true)) {
            throw new InvalidArrayStructure("The `{$operator}` is not a valid operator.");
        }

        return [
            'relation' => $relation,
            'column'   => $column,
            'operator' => $operator,
            'value'    => $value
        ];
    }

    /**
     * Build the query using "has" queries.
     *
     * @param  string  $relation
     * @param  string  $operator
     * @param  int  $count
     * @param  string  $boolean
     * @param  \Closure|null  $closure
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQueryUsingHas($relation, $operator = '>=', $count = 1, $boolean = 'and', $closure = null)
    {
        return $this->buildQuery()->has(
            $relation,
            $operator,
            $count,
            $boolean,
            $closure
        );
    }

    /**
     * Start building a new query or chain the existing one.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery()
    {
        return !empty($this->query) ? $this->query : $this->getModel()::query();
    }

    /**
     * Prepare the where conditions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     * @param  array  $wheres
     * @param  string  $method
     * @return void
     *
     * @throws \Ramadan\EasyModel\Exceptions\InvalidArrayStructure
     */
    protected function prepareBunchOfWhereClauses($q, $wheres, $method = 'where')
    {
        // We will loop through the given wheres array that must be in array of arrays
        // so, we have checked if each value represents an array.
        foreach ($wheres as $where) {
            if (!is_array($where)) {
                throw new InvalidArrayStructure("The `{$method}` array must be well defined.");
            }

            // If the array has one element it means that it was an associative array where
            // the key is the column and the value is the search value for that column
            // e.g. ['name' => 'Mahmoud Ramadan']
            // whereas, it has two elements it means that it was an indexed array where the first
            // element is the column and the second element is the search value for that column
            // e.g. ['name', 'Mahmoud Ramadan']
            // else, it means that the first element is the column and the second element is the operator
            // and the third element is the search value for that column
            // e.g. ['name', '=', 'Mahmoud Ramadan'].
            [$column, $operator, $value] = match (count($where)) {
                1 => [array_keys($where)[0], '=', array_values($where)[0]],
                2 => [$where[0], '=', $where[1]],
                3 => $where
            };

            if (!in_array(strtolower($operator), $this->getAllowedOperators(), true)) {
                throw new InvalidArrayStructure("The `{$operator}` is not a valid operator.");
            }

            $q->where($column, $operator, $value, $method === 'where' ? 'and' : 'or');
        }
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function execute()
    {
        return $this->query;
    }
}
