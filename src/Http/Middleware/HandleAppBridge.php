<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use EcomAuditors\InertiaShopifyApp\Exceptions\UnauthorizedException;
use Exception;
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
        $token = $request->query('token', $request->bearerToken());
        if (! $token) {
            throw new UnauthorizedException('Token missing from auth request.');
        }

        try {
            JWT::$leeway = 10;
            $payload = (array) JWT::decode($token, new Key(config('shopify-app.shared_secret'), 'HS256'));
            $domain = str_replace('https://', '', $payload['dest']);

            if (!Auth::guard(config('shopify-app.auth_guard'))->check()) {
                $user = config('shopify-app.user_model')::where('domain', $domain)->firstOrFail();
                Auth::guard(config('shopify-app.auth_guard'))->login($user);
            }
        } catch (Exception $e) {
            throw new UnauthorizedException('Invalid token in auth request.');
        }

        return $next($request);
    }
}