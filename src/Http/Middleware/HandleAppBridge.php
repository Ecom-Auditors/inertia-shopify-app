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
use Symfony\Component\HttpFoundation\Response;

class HandleAppBridge
{
    /**
     * @throws UnauthorizedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->query('token');

        try {
            JWT::$leeway = 10;
            $payload = (array) JWT::decode($token, new Key(config('shopify-app.shared_secret'), 'HS256'));
            $domain = str_replace('https://', '', $payload['dest']);

            if (!Auth::guard(config('shopify-app.auth_guard'))->check()) {
                $user = config('shopify-app.user_model')::where('domain', $domain)->firstOrFail();
                Auth::guard(config('shopify-app.auth_guard'))->login($user);
            }
        } catch (Exception $e) {
            if ($request->missing('shop')) {
                throw new UnauthorizedException('Token exception in auth request.');
            }

            return redirect()->route('auth.callback', [
                'shop' => $request->input('shop'),
            ]);
        }

        return $next($request);
    }
}