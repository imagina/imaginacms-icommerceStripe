<?php

namespace Modules\Icommercestripe\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Traits\CanPublishConfiguration;
use Modules\Core\Events\BuildingSidebar;
use Modules\Core\Events\LoadingBackendTranslations;
use Modules\Icommercestripe\Events\Handlers\RegisterIcommercestripeSidebar;

class IcommercestripeServiceProvider extends ServiceProvider
{
    use CanPublishConfiguration;
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBindings();
        $this->app['events']->listen(BuildingSidebar::class, RegisterIcommercestripeSidebar::class);

        $this->app['events']->listen(LoadingBackendTranslations::class, function (LoadingBackendTranslations $event) {
            $event->load('icommercestripes', Arr::dot(trans('icommercestripe::icommercestripes')));
            // append translations

        });
    }

    public function boot()
    {
        $this->publishConfig('icommercestripe', 'permissions');
        $this->publishConfig('icommercestripe', 'config');
        $this->publishConfig('icommercestripe', 'crud-fields');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

    private function registerBindings()
    {
        $this->app->bind(
            'Modules\Icommercestripe\Repositories\IcommerceStripeRepository',
            function () {
                $repository = new \Modules\Icommercestripe\Repositories\Eloquent\EloquentIcommerceStripeRepository(new \Modules\Icommercestripe\Entities\IcommerceStripe());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\Icommercestripe\Repositories\Cache\CacheIcommerceStripeDecorator($repository);
            }
        );
// add bindings

    }

    
}
