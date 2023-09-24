<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BillingController
{
    public function return(Request $request): RedirectResponse|Response
    {
        $shop = config('shopify-app.user_model')::firstWhere('myshopify_domain', $request->input('shop'));
        if ($request->missing('charge_id') || !$shop) {
            return response('Charge ID missing from billing response.', HttpResponse::HTTP_UNAUTHORIZED);
        }

        $recurringApplicationCharge = $shop->shopifyClient()->RecurringApplicationCharge($request->input('charge_id'))->get();

        if (!isset($recurringApplicationCharge['id']) || $recurringApplicationCharge['status'] !== 'active') {
            return response('Invalid charge ID in billing response.', HttpResponse::HTTP_UNAUTHORIZED);
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