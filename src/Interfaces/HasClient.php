<?php

namespace EcomAuditors\InertiaShopifyApp\Interfaces;

use PHPShopify\ShopifySDK;

interface HasClient
{
    /**
     * @return ShopifySDK
     */
    public function shopifyClient(): ShopifySDK;
}
