<?php

namespace EcomAuditors\InertiaShopifyApp\Actions;

use EcomAuditors\InertiaShopifyApp\Interfaces\HasClient;

class CreateSubscription
{
    /**
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function __invoke(HasClient $shop): string
    {
        $query = <<<Query
        mutation appSubscriptionCreate(\$test: Boolean, \$lineItems: [AppSubscriptionLineItemInput!]!, \$name: String!, \$returnUrl: URL!, \$trialDays: Int) {
          appSubscriptionCreate(test: \$test, lineItems: \$lineItems, name: \$name, returnUrl: \$returnUrl, trialDays: \$trialDays) {
            appSubscription {
                id
            }
            confirmationUrl
            userErrors {
              field
              message
            }
          }
        }
        Query;

        $variables = [
            'test' => config('shopify-app.billing.test', false),
            'name' => config('shopify-app.billing.name'),
            'trialDays' => config('shopify-app.billing.trial_days'),
            'returnUrl' => route('billing.return'),
            'lineItems' => []
        ];

        foreach (config('shopify-app.billing.plans') as $plan) {
            if (isset($plan['interval'])) {
                $lineItem = [
                    'plan' => [
                        'appRecurringPricingDetails' => [
                            'price' => [
                                'amount' => $plan['price'],
                                'currencyCode' => $plan['currency'],
                            ],
                            'interval' => $plan['interval'],
                        ],
                    ],
                ];
            } else {
                $lineItem = [
                    'plan' => [
                        'appUsagePricingDetails' => [
                            'terms' => $plan['terms'],
                            'cappedAmount' => [
                                'amount' => $plan['capped_amount'],
                                'currencyCode' => $plan['currency'],
                            ],
                        ],
                    ],
                ];
            }

            $variables['lineItems'][] = $lineItem;
        }

        $response = $shop->shopifyClient()->GraphQL->post($query, null, null, $variables);

        return $response['data']['appSubscriptionCreate']['confirmationUrl'];
    }
}