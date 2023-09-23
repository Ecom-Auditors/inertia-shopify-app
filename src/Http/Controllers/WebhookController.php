<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function __invoke(Request $request): Response
    {
        $topic = $request->header('x-shopify-topic');
        $domain = $request->header('x-shopify-shop-domain');

        $shop = config('shopify-app.user_model')::firstWhere('myshopify_domain', $domain);

        if ($shop && $topic === 'app_subscriptions/update') {
            $shop->billing_status = $request->input('payload.app_subscription.status');
            $shop->save();
        }

        if ($shop && $topic === 'app/uninstalled') {
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