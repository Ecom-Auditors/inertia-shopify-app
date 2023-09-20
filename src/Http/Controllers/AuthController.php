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

        ShopifySDK::config([
            'ShopUrl' => $request->input('shop'),
            'ApiKey' => config('shopify-app.api_key'),
            'SharedSecret' => config('shopify-app.shared_secret'),
        ]);
        $accessToken = AuthHelper::createAuthRequest(
            config('shopify-app.scopes'),
        );

        if (!isset($response['access_token'])) {
            throw new UnauthorizedException('Access token missing from auth response.');
        }

        $shopData = (new ShopifySDK([
            'ShopUrl' => $request->input('shop'),
            'AccessToken' => $response['access_token'],
        ]))->Shop->get();

        $user = config('shopify-app.user_model')::firstOrCreate(
            [
                'domain' => $request->input('shop'),
            ],
            [
                'email' => $shopData['email'],
                'name' => $shopData['shop_owner'],
                'access_token' => $response['access_token'],
                'shop' => $shopData['name'],
            ],
        );

        Cache::rememberForever(
            'host_'.$request->input('shop'),
            fn () => $request->input('host'),
        );
        Cache::rememberForever(
            'frame-ancestor_'.$request->input('shop'),
            fn () => 'https://'.$request->input('shop'),
        );

        return Inertia::render('Token', [
            'shop' => $request->input('shop'),
            'host' => $request->input('host'),
        ]);
    }
}