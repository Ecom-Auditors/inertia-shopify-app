<?php

namespace EcomAuditors\InertiaShopifyApp\Traits;

use PHPShopify\ShopifySDK;

trait ShopifyClient
{
    public function shopifyClient(): ShopifySDK
    {
        $config = [
            'ShopUrl' => $this->myshopify_domain,
            'AccessToken' => $this->access_token,
        ];

        return new ShopifySDK($config);
    }
}