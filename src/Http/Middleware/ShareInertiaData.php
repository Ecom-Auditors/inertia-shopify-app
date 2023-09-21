<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareInertiaData
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share(array_filter([
            'auth.user' => fn () => $request->user()
                ? $request->user()->only('id', 'name', 'email', 'shop', 'domain')
                : null,
            'flash' => $request->session()->get('flash', []),
            'errorBags' => function () {
                return collect(optional(Session::get('errors'))->getBags() ?: [])->mapWithKeys(function ($bag, $key) {
                    return [$key => $bag->messages()];
                })->all();
            },
        ]));

        return $next($request);
    }
}