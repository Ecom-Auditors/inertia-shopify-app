<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function __invoke(Request $request): Response
    {
        $topic = $_SERVER['HTTP_X_SHOPIFY_TOPIC'];
        $domain = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];

        $shop = config('shopify-app.user_model')::firstWhere('myshopify_domain', $domain);

        if ($shop && $topic === 'app_subscriptions/update') {
            $shop->billing_status = strtolower($request->input('app_subscription.status'));
            $shop->save();
        }

        if ($shop && $topic === 'app/uninstalled') {
            $shop->access_token = null;
            $shop->recurring_application_charge_id = null;
            $shop->billing_status = null;
            $shop->uninstalled_at = now();
            $shop->save();
        }

        $jobClass = config('shopify-app.webhooks')[$topic] ?? null;
        if ($jobClass) {
            $jobClass::dispatch($request->all());
        }

        return response('ok');
    }
}