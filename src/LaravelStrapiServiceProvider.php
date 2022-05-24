<?php

namespace Svnwa\LaravelStrapi;

use Illuminate\Support\ServiceProvider;

class LaravelStrapiServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'svnwa');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'svnwa');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/strapi.php', 'strapi');

        // Register the service the package provides.
        $this->app->singleton('strapi', function ($app) {
            return new LaravelStrapi;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-strapi'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/strapi.php' => config_path('strapi.php'),
        ], 'laravel-strapi');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/svnwa'),
        ], 'laravel-strapi.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/svnwa'),
        ], 'laravel-strapi.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/svnwa'),
        ], 'laravel-strapi.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
