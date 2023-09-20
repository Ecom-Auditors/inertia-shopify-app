# inertia-shopify-app
 
## Installation
``composer require ecom-auditors/inertia-shopify-app``

``php artisan vendor:publish --provider="EcomAuditors\InertiaShopifyApp\ShopifyAppServiceProvider"``

In App\Http\Kernel.php, replace ``\App\Http\Middleware\StartSession::class`` with ``\EcomAuditors\InertiaShopifyApp\Http\Middleware\StartSession::class``