<?php

namespace Ramadan\EasyModel;

use Illuminate\Support\ServiceProvider;

class EasyModelServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(EasyModel::class, fn() => new EasyModel);
    }
}
