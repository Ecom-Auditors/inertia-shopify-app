<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use EcomAuditors\InertiaShopifyApp\Exceptions\UnauthorizedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use PHPShopify\AuthHelper;
use PHPShopify\ShopifySDK;

class AuthController extends Controller
{
    public function token(Request $request): Response
    {
        $validated = $request->validate([
            'host' => 'required',
            'shop' => 'required',
        ]);

        return Inertia::render('Auth', [
            ...$validated,
            'step' => 'token',
        ]);
    }

    /**
     * @throws \PHPShopify\Exception\SdkException
     * @throws UnauthorizedException
     * @throws \PHPShopify\Exception\CurlException
     * @throws \PHPShopify\Exception\ApiException
     */
    public function callback(Request $request): Response|RedirectResponse
    {
        if ($request->isNotFilled('shop')) {
            throw new UnauthorizedException('Shop missing from auth response.');
        }

        $shop = $request->input('shop');
        $host = $request->input('host');

        ShopifySDK::config([
            'ShopUrl' => $shop,
            'ApiKey' => config('shopify-app.api_key'),
            'SharedSecret' => config('shopify-app.shared_secret'),
        ]);
        $accessTokenOrAuthUrl = AuthHelper::createAuthRequest(
            config('shopify-app.scopes'),
            return: true,
        );
        if (filter_var($accessTokenOrAuthUrl, FILTER_VALIDATE_URL)) {
            if ($request->input('embedded') === '1') {
                return Inertia::render('Auth', [
                    'step' => 'grant',
                    'shop' => $shop,
                    'host' => $host,
                    'url' => $accessTokenOrAuthUrl,
                ]);
            }
            return redirect()->away($accessTokenOrAuthUrl);
        }

        if (!isset($accessTokenOrAuthUrl)) {
            throw new UnauthorizedException('Access token missing from auth response.');
        }

        $shopData = (new ShopifySDK([
            'ShopUrl' => $shop,
            'AccessToken' => $accessTokenOrAuthUrl,
        ]))->Shop->get();

        $user = config('shopify-app.user_model')::firstOrCreate(
            [
                'myshopify_domain' => $shop,
            ],
            [
                'domain' => $shopData['domain'],
                'email' => $shopData['email'],
                'name' => $shopData['shop_owner'],
                'access_token' => $accessTokenOrAuthUrl,
                'shop' => $shopData['name'],
            ],
        );

        Cache::forever('host_'.$shop, $host);
        Cache::forever('frame-ancestor_'.$shop, 'https://'.$shop);

        $params = [
            'step' => 'token',
            'shop' => $shop,
            'host' => $host,
        ];

        if ($request->input('embedded') === '1') {
            return redirect()->route('auth.token', $params);
        }
        $apiKey = config('shopify-app.api_key');
        $decodedHost = base64_decode($host, true);
        return redirect()->away(
            'https://'.$decodedHost.'/apps/'.$apiKey.'/auth/token?'.http_build_query($params)
        );
    }
}