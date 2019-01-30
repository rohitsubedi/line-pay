<?php

namespace Rohit\LinePay;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;

class LinePayServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('line-pay.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $packageConfigFile = __DIR__ . '/../../config/config.php';

        $this->mergeConfigFrom(
            $packageConfigFile, 'line-pay'
        );

        $this->app->singleton(LinePay::class, function () {
            return new LinePay(new Client, $this->app->make('validator'));
        });

        $this->app->alias(LinePay::class, 'line-pay');
    }
}
