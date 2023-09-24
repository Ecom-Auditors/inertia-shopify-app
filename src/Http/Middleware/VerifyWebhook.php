<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use EcomAuditors\InertiaShopifyApp\Exceptions\UnauthorizedException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $hmac = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? null;
        $contents = file_get_contents('php://input');
        $calculatedHmac = base64_encode(hash_hmac(
            'sha256',
            $contents,
            config('shopify-app.shared_secret'),
            true,
        ));

        if (empty($hmac) || !hash_equals($hmac, $calculatedHmac)) {
            throw new UnauthorizedException('Invalid webhook signature.');
        }

        return $next($request);
    }
}