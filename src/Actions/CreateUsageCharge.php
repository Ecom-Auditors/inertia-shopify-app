<?php

namespace EcomAuditors\InertiaShopifyApp\Actions;

use EcomAuditors\InertiaShopifyApp\Interfaces\HasClient;

class CreateUsageCharge
{
    /**
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function __invoke(HasClient $shop, string $description, mixed $price): array
    {
        return $shop->shopifyClient()->RecurringApplicationCharge($shop->recurring_application_charge_id)->UsageCharge->post([
            'description' => $description,
            'price' => $price,
        ]);
    }
}