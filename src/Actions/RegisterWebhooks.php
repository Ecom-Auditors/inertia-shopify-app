<?php

namespace EcomAuditors\InertiaShopifyApp\Actions;

use EcomAuditors\InertiaShopifyApp\Interfaces\HasClient;

class RegisterWebhooks
{
    /**
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function __invoke(HasClient $shop): void
    {
        foreach (config('shopify-app.webhooks') as $topic => $jobClass) {
            $shop->shopifyClient()->Webhook->post([
                'topic' => $topic,
                'address' => route('webhooks.shopify'),
            ]);
        }

        $shop->shopifyClient()->Webhook->post([
            'topic' => 'app_subscriptions/update',
            'address' => route('webhooks.shopify'),
        ]);

        $shop->shopifyClient()->Webhook->post([
            'topic' => 'app/uninstalled',
            'address' => route('webhooks.shopify'),
        ]);
    }
}