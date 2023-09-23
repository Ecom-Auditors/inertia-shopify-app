<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use EcomAuditors\InertiaShopifyApp\Exceptions\UnauthorizedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BillingController
{
    public function return(Request $request): RedirectResponse
    {
        $shop = config('shopify-app.user_model')::firstWhere('myshopify_domain', $request->input('shop'));
        if ($request->missing('charge_id') || !$shop) {
            throw new UnauthorizedException('Charge ID missing from billing response.');
        }

        $recurringApplicationCharge = $shop->shopifyClient()->RecurringApplicationCharge($request->input('charge_id'))->get();

        if (!isset($recurringApplicationCharge['id']) || $recurringApplicationCharge['status'] !== 'active') {
            throw new UnauthorizedException('Invalid charge ID in billing response.');
        }

        $shop->recurring_application_charge_id = $recurringApplicationCharge['id'];
        $shop->billing_status = strtolower($recurringApplicationCharge['status']);
        $shop->save();

        return redirect()->route('auth.token', [
            'shop' => $shop->myshopify_domain,
            'host' => Cache::get('host_'.$shop->myshopify_domain),
        ]);
    }
}