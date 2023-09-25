<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ProtectIframe
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->missing('shop') && !Auth::guard(config('shopify-app.auth_guard'))->check()) {
            return response('Shop missing from auth response.', Response::HTTP_UNAUTHORIZED);
        }

        $shopUrl = $request->input('shop', optional($request->user())->myshopify_domain);

        $host = Cache::get('host_'.$shopUrl);
        $frameAncestor = Cache::get('frame-ancestor_'.$shopUrl);

        Inertia::share('host', $host);
        Inertia::share('sessionId', $request->session()->getId());

        return $next($request)->header(
            'Content-Security-Policy',
            'frame-ancestors https://admin.shopify.com '.$frameAncestor,
        );
    }
}