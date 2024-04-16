<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\BackblazeService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton(BackblazeService::class, function ($app) {

            return new BackblazeService();
        });
    }
}
