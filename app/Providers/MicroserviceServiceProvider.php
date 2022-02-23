<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MicroserviceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        
        $this->app->singleton('http', function ($app) {
            return new \Illuminate\Http\Client\Factory;
        });

        $this->app->singleton('microservice', function ($app) {
            return (new \App\Extensions\Microservice\Factory($app));
        });
    }

    public function boot()
    {
        $this->app['microservice']->routing();
    }
}
