<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard(config('shopify-app.auth_guard'))->check() || $request->user()->billing_status !== 'active') {
            return redirect()->route('auth.callback', [
                'shop' => $request->user()->myshopify_domain,
                'host' => Cache::get('host_'.$request->user()->myshopify_domain),
                'embedded' => '1',
            ]);
        }

        return $next($request);
    }
}