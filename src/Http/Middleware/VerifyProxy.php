<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use EcomAuditors\InertiaShopifyApp\Exceptions\UnauthorizedException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class VerifyProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        $data = $request->query();
        $sharedSecret = config('shopify-app.shared_secret');

        $signature = $data['signature'];
        unset($data['signature']);

        $dataString = collect($data)
            ->map(fn ($v, $k) => $k.'='.implode(',', Arr::wrap($v)))
            ->sort()
            ->join('');

        $hmac = hash_hmac('sha256', $dataString, $sharedSecret);

        if (!hash_equals($hmac, $signature)) {
            throw new UnauthorizedException('Invalid signature in proxy request.');
        }

        return $next($request);
    }
}