<?php

namespace Denngarr\Seat\SeatSrp;

use Denngarr\Seat\SeatSrp\Commands\InsuranceUpdate;
use Seat\Services\AbstractSeatPlugin;

class SrpServiceProvider extends AbstractSeatPlugin
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addCommands();
        $this->add_routes();
        // $this->add_middleware($router);
        $this->add_views();
        $this->add_publications();
        $this->add_translations();
        $this->apply_custom_configuration();
    }

    /**
     * Include the routes.
     */
    public function add_routes()
    {
        if (! $this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    public function add_translations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'srp');
    }

    /**
     * Set the path and namespace for the views.
     */
    public function add_views()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'srp');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/srp.config.php', 'srp.config');

        $this->mergeConfigFrom(
            __DIR__ . '/Config/srp.sidebar.php', 'package.sidebar');

        $this->mergeConfigFrom(
            __DIR__ . '/Config/srp.permissions.php', 'web.permissions');
    }

    public function add_publications()
    {
        $this->publishes([
            __DIR__ . '/resources/assets'     => public_path('web'),
            __DIR__ . '/database/migrations/' => database_path('migrations')
        ]);
    }

    private function addCommands()
    {
        $this->commands([
            InsuranceUpdate::class,
        ]);
    }

    /**
     * Apply any configuration overrides to those config/
     * files published using php artisan vendor:publish.
     *
     * In the case of this service provider, this is mostly
     * configuration items for L5-Swagger.
     */
    public function apply_custom_configuration()
    {
        // Tell L5-swagger where to find annotations. These form
        // part of the controllers themselves.

        // ensure current annotations setting is an array of path or transform into it
        $current_annotations = config('l5-swagger.paths.annotations');
        if (! is_array($current_annotations))
            $current_annotations = [$current_annotations];

        // merge paths together and update config
        config([
            'l5-swagger.paths.annotations' => array_unique(array_merge($current_annotations, [
                __DIR__ . '/Http/Controllers',
            ])),
        ]);
    }

    public function getName(): string
    {
        return 'SRP';
    }

    /**
     * @inheritDoc
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/RazeSoldier/seat-srp';
    }

    /**
     * @inheritDoc
     */
    public function getPackagistPackageName(): string
    {
        return 'seat-srp';
    }

    /**
     * @inheritDoc
     */
    public function getPackagistVendorName(): string
    {
        return 'razesoldier';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '4.1.4';
    }
}
