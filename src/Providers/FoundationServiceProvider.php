<?php

declare(strict_types=1);

namespace Cortex\Foundation\Providers;

use Cortex\Foundation\Models\Menu;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Cortex\Foundation\Http\Middleware\TrailingSlashEnforce;
use Cortex\Foundation\Http\Middleware\NotificationMiddleware;
use Cortex\Foundation\Overrides\Illuminate\Routing\Redirector;
use Cortex\Foundation\Overrides\Yajra\Datatables\Html\Builder;
use Cortex\Foundation\Overrides\Illuminate\Routing\UrlGenerator;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Cortex\Foundation\Overrides\Mcamara\LaravelLocalization\LaravelLocalization;

class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Early set application locale globaly
        $this->app['laravellocalization']->setLocale();

        // Load resources
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'cortex/foundation');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'cortex/foundation');
        ! $this->app->runningInConsole() || $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Publish Resources
        ! $this->app->runningInConsole() || $this->publishResources();

        $this->app->booted(function () {
            if ($this->app->routesAreCached()) {
                require $this->app->getCachedRoutesPath();
            } else {
                $this->app['router']->getRoutes()->refreshNameLookups();
                $this->app['router']->getRoutes()->refreshActionLookups();
            }
        });
    }

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register()
    {
        $this->overrideNotificationMiddleware();
        $this->registerDevelopmentProviders();
        $this->overrideLaravelLocalization();
        $this->overrideUrlGenerator();
        $this->overrideRedirector();
        $this->bindBladeCompiler();

        // Merge config
        $this->mergeConfigFrom(realpath(__DIR__.'/../../config/config.php'), 'cortex.foundation');

        // Register custom datatables html builder
        $this->app->singleton('cortex.foundation.datatables.html', function () {
            return $this->app->make(Builder::class);
        });

        $this->prependMiddleware();
        $this->registerMenus();
    }

    /**
     * Override notification middleware.
     *
     * @return void
     */
    protected function overrideNotificationMiddleware()
    {
        $this->app->singleton('Cortex\Foundation\Http\Middleware\NotificationMiddleware', function ($app) {
            return new NotificationMiddleware(
                $app['session.store'],
                $app['notification'],
                $app['config']->get('notification.session_key')
            );
        });
    }

    /**
     * Bind blade compiler.
     *
     * @return void
     */
    protected function bindBladeCompiler()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {

            // @alerts('container')
            $bladeCompiler->directive('alerts', function ($container = null) {
                if (strcasecmp('()', $container) === 0) {
                    $container = null;
                }

                return "<?php echo app('notification')->container({$container})->show(); ?>";
            });
        });
    }

    /**
     * Prepends the bootstrap middleware.
     *
     * @return void
     */
    protected function prependMiddleware()
    {
        if ($this->app['config']->get('rinvex.cortex.route.locale_prefix') && $this->app['config']->get('rinvex.cortex.route.locale_redirect')) {
            $this->app[Kernel::class]->prependMiddleware(LaravelLocalizationRedirectFilter::class);
        }

        if ($this->app['config']->get('rinvex.cortex.route.trailing_slash')) {
            $this->app[Kernel::class]->prependMiddleware(TrailingSlashEnforce::class);
        }
    }

    /**
     * Registers the Generic bindings.
     *
     * @return void
     */
    protected function registerDevelopmentProviders()
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
            $this->app->register(\Clockwork\Support\Laravel\ClockworkServiceProvider::class);
            //$this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Override the Redirector instance.
     *
     * @return void
     */
    protected function overrideRedirector()
    {
        $this->app->singleton('redirect', function ($app) {
            $redirector = new Redirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });
    }

    /**
     * Override the UrlGenerator instance.
     *
     * @return void
     */
    protected function overrideUrlGenerator()
    {
        $this->app->singleton('url', function ($app) {
            $routes = $app['router']->getRoutes();

            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $app->instance('routes', $routes);

            $url = new UrlGenerator(
                $routes, $app->rebinding(
                    'request', $this->requestRebinder()
                )
            );

            $url->setSessionResolver(function () {
                return $this->app['session'];
            });

            // If the route collection is "rebound", for example, when the routes stay
            // cached for the application, we will need to rebind the routes on the
            // URL generator instance so it has the latest version of the routes.
            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    /**
     * Get the URL generator request rebinder.
     *
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }

    /**
     * Override the LaravelLocalization instance.
     *
     * @return void
     */
    protected function overrideLaravelLocalization()
    {
        $this->app->singleton('laravellocalization', function () {
            return new LaravelLocalization();
        });
    }

    /**
     * Publish resources.
     *
     * @return void
     */
    protected function publishResources()
    {
        $this->publishes([realpath(__DIR__.'/../../config/config.php') => config_path('cortex.foundation.php')], 'config');
        $this->publishes([realpath(__DIR__.'/../../resources/lang') => resource_path('lang/vendor/cortex/foundation')], 'lang');
        $this->publishes([realpath(__DIR__.'/../../resources/views') => resource_path('views/vendor/cortex/foundation')], 'views');
    }

    /**
     * Register menus.
     *
     * @return void
     */
    protected function registerMenus()
    {
        $this->app->singleton(Menu::class, function () {
            return Menu::new();
        });

        $this->app->alias(Menu::class, 'menu');

        $this->registerBackendMenus();
        $this->registerUserareaMenus();
        $this->registerFrontendMenus();
    }

    /**
     * Register backend menus.
     *
     * @return void
     */
    protected function registerBackendMenus()
    {
        $app = $this->app;

        Menu::macro('backendSidebar', function ($section = null) use ($app) {
            $app->bound('menu.backend.sidebar') || $app->singleton('menu.backend.sidebar', function () {
                return Menu::new();
            });

            return $app['menu.backend.sidebar']->setSection($section);
        });

        Menu::macro('backendTopbar', function ($section = null) use ($app) {
            $app->bound('menu.backend.topbar') || $app->singleton('menu.backend.topbar', function () {
                return Menu::new();
            });

            return $app['menu.backend.topbar']->setSection($section);
        });
    }

    /**
     * Register userarea menus.
     *
     * @return void
     */
    protected function registerUserareaMenus()
    {
        $app = $this->app;

        Menu::macro('userareaTopbar', function ($section = null) use ($app) {
            $app->bound('menu.userarea.topbar') || $app->singleton('menu.userarea.topbar', function () {
                return Menu::new();
            });

            return $app['menu.userarea.topbar']->setSection($section);
        });
    }

    /**
     * Register frontend menus.
     *
     * @return void
     */
    protected function registerFrontendMenus()
    {
        $app = $this->app;

        Menu::macro('frontendTopbar', function ($section = null) use ($app) {
            $app->bound('menu.frontend.topbar') || $app->singleton('menu.frontend.topbar', function () {
                return Menu::new();
            });

            return $app['menu.frontend.topbar']->setSection($section);
        });
    }
}
