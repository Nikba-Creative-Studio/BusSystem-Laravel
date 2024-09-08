<?php
namespace Nikba\BusSystem;

use Illuminate\Support\ServiceProvider;

class BusSystemServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publush configuration file
        $this->publishes([
            __DIR__.'/../config/bussystem.php' => config_path('bussystem.php'),
        ], 'config');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge configuration file
        $this->mergeConfigFrom(
            __DIR__.'/../config/bussystem.php', 'bussystem'
        );

        // Register the service the package provides
        $this->app->singleton('busapi', function ($app) {
            return new BusApiService();
        });
    }
}
