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
                'domain' => $shop,
            ],
            [
                'email' => $shopData['email'],
                'name' => $shopData['shop_owner'],
                'access_token' => $accessTokenOrAuthUrl,
                'shop' => $shopData['name'],
            ],
        );

        Cache::rememberForever(
            'host_'.$shop,
            fn () => $request->input('host'),
        );
        Cache::rememberForever(
            'frame-ancestor_'.$shop,
            fn () => 'https://'.$shop,
        );

        dd('test');

        return Inertia::render('Token', [
            'shop' => $shop,
            'host' => $request->input('host'),
        ]);
    }
}