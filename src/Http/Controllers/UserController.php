<?php

namespace EcomAuditors\InertiaShopifyApp\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Profile/Show');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'shop' => 'required|string|max:191',
            'name' => 'required|string|max:191',
            'email' => [
                'required',
                'string',
                'email',
                'max:191',
                Rule::unique(app(config('shopify-app.user_model'))->getTable())->ignore(auth()->id()),
            ],
        ])->validateWithBag('updateUser');

        $user = $request->user();
        $user->fill($validated);
        $user->save();

        return redirect()->route('user.show')->banner('Profile updated successfully.');
    }
}