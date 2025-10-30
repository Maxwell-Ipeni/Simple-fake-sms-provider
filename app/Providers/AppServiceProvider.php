<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the SmsCallbackService implementation for injection
        $this->app->bind(\App\Services\SmsCallbackServiceInterface::class, function ($app) {
            return new \App\Services\SmsCallbackService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
