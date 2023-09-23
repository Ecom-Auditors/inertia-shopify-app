<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use EcomAuditors\InertiaShopifyApp\Exceptions\UnauthorizedException;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PHPShopify\AuthHelper;
use PHPShopify\ShopifySDK;
use Symfony\Component\HttpFoundation\Response;

class HandleAppBridge
{
    /**
     * @throws UnauthorizedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $domain = null;

            if ($token = $request->bearerToken()) {
                JWT::$leeway = 10;
                $payload = (array) JWT::decode($token, new Key(config('shopify-app.shared_secret'), 'HS256'));
                $domain = str_replace('https://', '', $payload['dest']);
            }

            if ($request->has('hmac')) {
                ShopifySDK::config([
                    'ShopUrl' => $request->input('shop'),
                    'ApiKey' => config('shopify-app.api_key'),
                    'SharedSecret' => config('shopify-app.shared_secret'),
                ]);
                if (AuthHelper::verifyShopifyRequest()) {
                    $domain = $request->input('shop');
                }
            }

            if (!$domain) {
                throw new UnauthorizedException('Token missing in auth request.');
            }

            $user = config('shopify-app.user_model')::where('myshopify_domain', $domain)
                ->whereNotNull('access_token')
                ->firstOrFail();

            Auth::guard(config('shopify-app.auth_guard'))->login($user);
        } catch (Exception $e) {
            if ($request->missing('shop')) {
                throw new UnauthorizedException('Token exception in auth request.');
            }
            $shopUrl = $request->input('shop');

            return redirect()->route('auth.callback', [
                'shop' => $shopUrl,
                'host' => Cache::get('host_'.$shopUrl),
                'embedded' => '1',
            ]);
        }

        return $next($request);
    }
}