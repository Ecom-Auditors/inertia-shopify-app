<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HandleAppBridge
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $domain = null;

            if ($token = $request->bearerToken()) {
                JWT::$leeway = 10;
                $payload = (array) JWT::decode($token, new Key(config('shopify-app.shared_secret'), 'HS256'));
                $domain = str_replace('https://', '', $payload['dest']);
            }

            if (!$domain) {
                throw new Exception('Token missing in auth request.');
            }

            $user = config('shopify-app.user_model')::where('myshopify_domain', $domain)
                ->whereNotNull('access_token')
                ->firstOrFail();

            Auth::guard(config('shopify-app.auth_guard'))->login($user);
        } catch (Exception $e) {
            if ($request->missing('shop')) {
                return response('Token exception in auth request.', Response::HTTP_UNAUTHORIZED);
            }
            $shopUrl = $request->input('shop');

            return redirect()->route('auth.token', [
                'shop' => $shopUrl,
                'host' => Cache::get('host_'.$shopUrl),
            ]);
        }

        return $next($request);
    }
}