<?php

namespace OptimistDigital\MenuBuilder;

use Laravel\Nova\Nova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use OptimistDigital\MenuBuilder\Http\Middleware\Authorize;
use OptimistDigital\MenuBuilder\Commands\CreateMenuItemType;
use OptimistDigital\NovaTranslationsLoader\LoadsNovaTranslations;

class MenuBuilderServiceProvider extends ServiceProvider
{
    use LoadsNovaTranslations;

    public function boot()
    {
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'nova-menu');

        // Load translations
        $this->loadTranslations(__DIR__ . '/../resources/lang', 'nova-menu-builder', true);

        // Load migrations
        if (config('nova-menu.auto_load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Publish data
        $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'nova-menu-builder-migrations');
        $this->publishes([__DIR__ . '/../config' => config_path()], 'nova-menu-builder-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateMenuItemType::class
            ]);
        }

        // Register resource
        Nova::resources([
            MenuBuilder::getMenuResource()
        ]);

        // Register routes
        $this->app->booted(function () {
            $this->routes();
        });

        Validator::extend('unique_menu', function ($attribute, $value, $parameters, $validator) {
            // Check if menu has unique attribute defined.
            $uniqueParams = join(',', $parameters);
            return (MenuBuilder::getMenus()[$value]['unique'] ?? true)
                // If unique attribute is true or not defined, call unique validator
                ? Validator::make([$attribute => $value], ['slug' => "unique:$uniqueParams"])->validate()
                : true;
        }, '');
    }

    protected function routes()
    {
        if ($this->app->routesAreCached()) return;

        Route::middleware(['nova', Authorize::class])
            ->namespace('OptimistDigital\MenuBuilder\Http\Controllers')
            ->prefix('nova-vendor/nova-menu')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
