<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'profile_image' => $request->user() ? $request->user()->getFirstMediaUrl('profile_image') : null,
                'payment_account' => $request->user() ? $request->user()->paymentAccounts : null,
            ],
            'toast' => session('toast'),
            'notification' => session('notification'),
            'locale' => session('locale') ? session('locale') : app()->getLocale(),
            'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name')->toArray() : 'no permission',
        ];
    }
}
