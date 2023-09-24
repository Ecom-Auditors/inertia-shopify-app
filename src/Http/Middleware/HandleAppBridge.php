<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPShopify\AuthHelper;
use PHPShopify\ShopifySDK;
use Symfony\Component\HttpFoundation\Response;

class HandleAppBridge
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $hmacResult = $this->verifyHmac($request);
        if ($hmacResult === false) {
            return response('Unable to verify signature.', Response::HTTP_UNAUTHORIZED);
        }

        if (Str::contains($request->getRequestUri(), ['/auth', '/billing'])) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (!$token) {
            $shop = $this->getShop($request);
            if (!$shop) {
                return response('Unable to verify signature.', Response::HTTP_UNAUTHORIZED);
            }

            return $this->getShopByDomain($shop) ? $this->tokenRedirect($request) : $this->handleInvalidShop($shop);
        }

        try {
            JWT::$leeway = 10;
            $payload = (array) JWT::decode($token, new Key(config('shopify-app.shared_secret'), 'HS256'));
            $shop = parse_url($payload['dest'], PHP_URL_HOST);
        } catch (Exception $e) {
            return $this->tokenRedirect($request);
        }

        $loginResult = $this->loginShop($shop);
        if (! $loginResult) {
            return $this->handleInvalidShop($shop);
        }

        return $next($request);
    }

    protected function getShop(Request $request): ?string
    {
        return $request->input('shop');
    }

    protected function getHost(Request $request): string
    {
        $shop = $this->getShop($request);
        return $request->input(
            'host',
            Cache::get(
                'host_'.$shop,
                fn () => base64_encode($shop.'/admin')
            )
        );
    }

    protected function handleInvalidShop($domain): RedirectResponse
    {
        return redirect()->route('auth.callback', [
            'shop' => $domain,
            'host' => $this->getHost($domain),
            'embedded' => '1',
        ]);
    }

    protected function verifyHmac(Request $request): ?bool
    {
        $hmac = $request->input('hmac');
        if ($hmac === null) {
            return null;
        }

        ShopifySDK::config([
            'ShopUrl' => $this->getShop($request),
            'ApiKey' => config('shopify-app.api_key'),
            'SharedSecret' => config('shopify-app.shared_secret'),
        ]);

        try {
            return AuthHelper::verifyShopifyRequest();
        } catch (Exception $e) {
            return false;
        }
    }

    protected function loginShop(string $domain): bool
    {
        $shop = $this->getShopByDomain($domain);
        if (!$shop) {
            return false;
        }

        Auth::guard(config('shopify-app.auth_guard'))->login($shop);

        return true;
    }

    /**
     * Redirect to token route.
     *
     * @param Request $request The request object.
     *
     * @return RedirectResponse
     */
    protected function tokenRedirect(Request $request): RedirectResponse
    {
        $dest = Str::start($request->path(), '/');

        if ($request->query()) {
            $filteredQuery = collect($request->query())->except([
                'hmac',
                'locale',
                'new_design_language',
                'timestamp',
                'session',
                'shop',
            ]);

            if ($filteredQuery->isNotEmpty()) {
                $dest .= '?'.http_build_query($filteredQuery->toArray());
            }
        }

        return redirect()->route('auth.token', [
            'shop' => $this->getShop($request),
            'host' => $this->getHost($request),
            'dest' => $dest,
        ]);
    }

    protected function getShopByDomain(string $domain): ?Authenticatable
    {
        return config('shopify-app.user_model')::where('myshopify_domain', $domain)
            ->whereNotNull('access_token')
            ->whereNull('uninstalled_at')
            ->first();
    }
}