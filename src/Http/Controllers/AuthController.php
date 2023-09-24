<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use EcomAuditors\InertiaShopifyApp\Actions\CreateSubscription;
use EcomAuditors\InertiaShopifyApp\Actions\RegisterWebhooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use PHPShopify\AuthHelper;
use PHPShopify\ShopifySDK;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthController extends Controller
{
    public function token(Request $request): Response
    {
        $validated = $request->validate([
            'host' => 'required',
            'shop' => 'required',
            'url' => 'nullable|string',
            'target' => 'nullable|string',
        ]);

        return Inertia::render('Auth', $validated);
    }

    /**
     * @throws \PHPShopify\Exception\SdkException
     * @throws \PHPShopify\Exception\CurlException
     * @throws \PHPShopify\Exception\ApiException
     */
    public function callback(
        Request $request,
        RegisterWebhooks $registerWebhooks,
        CreateSubscription $createSubscription,
    ): Response|RedirectResponse|\Illuminate\Http\Response {
        if ($request->isNotFilled('shop')) {
            return response('Shop missing from auth response.', HttpResponse::HTTP_UNAUTHORIZED);
        }

        $shop = $request->input('shop');
        $host = $request->input('host');
        $params = [
            'shop' => $shop,
            'host' => $host,
        ];

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
                    ...$params,
                    'url' => $accessTokenOrAuthUrl,
                ]);
            }
            return redirect()->away($accessTokenOrAuthUrl);
        }

        if (!isset($accessTokenOrAuthUrl)) {
            return response('Access token missing from auth response.', HttpResponse::HTTP_UNAUTHORIZED);
        }

        $shopData = (new ShopifySDK([
            'ShopUrl' => $shop,
            'AccessToken' => $accessTokenOrAuthUrl,
        ]))->Shop->get();

        $user = config('shopify-app.user_model')::firstOrNew(['myshopify_domain' => $shop]);

        $user->domain = $shopData['domain'];
        $user->email = $shopData['email'];
        $user->name = $shopData['shop_owner'];
        $user->shop = $shopData['name'];
        $user->access_token = $accessTokenOrAuthUrl;

        if (!$user->exists || $user->uninstalled_at) {
            $registerWebhooks($user);

            if (config('shopify-app.billing.enabled')) {
                $confirmationUrl = $createSubscription($user);
                $params['url'] = $confirmationUrl;
            }
        }

        $user->uninstalled_at = null;
        $user->save();

        Cache::forever('host_'.$shop, $host);
        Cache::forever('frame-ancestor_'.$shop, 'https://'.$shop);

        if ($request->input('embedded') === '1') {
            return Inertia::render('Auth', $params);
        }
        $apiKey = config('shopify-app.api_key');
        $decodedHost = base64_decode($host, true);
        return redirect()->away(
            'https://'.$decodedHost.'/apps/'.$apiKey.'/auth/token?'.http_build_query($params)
        );
    }
}