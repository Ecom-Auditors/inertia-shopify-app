<?php

namespace EcomAuditors\InertiaShopifyApp;

use EcomAuditors\InertiaShopifyApp\Http\Middleware\VerifyProxy;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\HandleAppBridge;
use EcomAuditors\InertiaShopifyApp\Http\Middleware\ShareInertiaData;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;

class ShopifyAppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/shopify-app.php', 'shopify-app');

        config([
            'shopify-app.user_model' => config('auth.providers.'.config('shopify-app.auth_provider').'.model'),
        ]);
    }

    public function boot(): void
    {
        $this->configureRoutes();
        $this->configureMacros();
        $this->configureMiddleware();
        $this->configurePublishing();
    }

    protected function configureRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    protected function configurePublishing()
    {
        $this->publishes([
            __DIR__.'/../stubs/config/shopify-app.php' => config_path('shopify-app.php'),
        ], 'shopify-app-config');

        $this->publishes([
            __DIR__.'/../database/migrations/add_shopify_columns_to_users_table.php' => database_path('migrations/add_shopify_columns_to_users_table.php'),
        ], 'shopify-app-migrations');
    }

    protected function configureMiddleware()
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->appendMiddlewareToGroup('web', ShareInertiaData::class);
        $kernel->appendToMiddlewarePriority(ShareInertiaData::class);
        $kernel->prependToMiddlewarePriority(HandleAppBridge::class);

        $this->app['router']->aliasMiddleware('app.bridge', HandleAppBridge::class);
        $this->app['router']->aliasMiddleware('app.proxy', VerifyProxy::class);
    }

    protected function configureMacros()
    {
        if (!RedirectResponse::hasMacro('banner')) {
            RedirectResponse::macro('banner', function ($message) {
                /** @var RedirectResponse $this */
                return $this->with('flash', [
                    'bannerStyle' => 'success',
                    'banner' => $message,
                ]);
            });
        }

        if (!RedirectResponse::hasMacro('dangerBanner')) {
            RedirectResponse::macro('dangerBanner', function ($message) {
                /** @var RedirectResponse $this */
                return $this->with('flash', [
                    'bannerStyle' => 'danger',
                    'banner' => $message,
                ]);
            });
        }
    }
}