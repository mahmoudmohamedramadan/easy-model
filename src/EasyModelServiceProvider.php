<?php

namespace Ramadan\EasyModel;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

class EasyModelServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/easy-model.php', 'easy-model');

        $this->app->singleton(EasyModel::class, fn() => new EasyModel);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/easy-model.php' => config_path('easy-model.php'),
        ], 'easy-model-config');

        if (config('easy-model.register_builder_macros', true)) {
            $this->registerBuilderMacros();
        }
    }

    /**
     * Register helper macros on the Eloquent and Query builders.
     */
    protected function registerBuilderMacros()
    {
        EloquentBuilder::macro('keywordSearch', function (?string $keyword, array $columns, bool $strict = false) {
            /** @var EloquentBuilder $this */
            if ($keyword === null || $keyword === '' || empty($columns)) {
                return $this;
            }

            return $this->where(function ($inner) use ($keyword, $columns, $strict) {
                foreach ($columns as $column) {
                    $strict
                        ? $inner->orWhere($column, '=', $keyword)
                        : $inner->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
            });
        });

        QueryBuilder::macro('keywordSearch', function (?string $keyword, array $columns, bool $strict = false) {
            /** @var QueryBuilder $this */
            if ($keyword === null || $keyword === '' || empty($columns)) {
                return $this;
            }

            return $this->where(function ($inner) use ($keyword, $columns, $strict) {
                foreach ($columns as $column) {
                    $strict
                        ? $inner->orWhere($column, '=', $keyword)
                        : $inner->orWhere($column, 'LIKE', '%' . $keyword . '%');
                }
            });
        });

        EloquentBuilder::macro('orderByAggregateRelation', function (string $relation, string $column, string $aggregate, string $direction = 'asc') {
            /** @var EloquentBuilder $this */
            $this->withAggregate($relation, $column, $aggregate);

            $alias = sprintf(
                '%s_%s%s',
                str_replace('.', '_', $relation),
                strtolower($aggregate),
                strtolower($aggregate) === 'count' && $column === '*' ? '' : '_' . $column
            );

            return $this->orderBy($alias, $direction);
        });

        EloquentBuilder::macro('orderByCountRelation', function (string $relation, string $direction = 'asc') {
            /** @var EloquentBuilder $this */
            return $this->orderByAggregateRelation($relation, '*', 'count', $direction);
        });
    }
}
