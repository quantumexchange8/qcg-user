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
                'user' => fn () => $request->user()
                            ? array_merge(
                                $request->user()->only(['id', 'first_name', 'chinese_name', 'email', 'dial_code', 'phone', 'referral_code', 'kyc_approval', 'kyc_approved_at', 'kyc_approval_description', 'role', 'has_new_forum_posts' , 'has_new_ticket_replies', 'ticket_agent_access']),
                                [
                                    'roles' => $request->user()->roles->map(fn ($role) => [
                                        'id' => $role->id,
                                        'name' => $role->name,
                                    ]),
                                ]
                            )
                            : null,
                'profile_photo' => $request->user() ? $request->user()->getFirstMediaUrl('profile_photo') : null,
                'payment_account' => $request->user() ? $request->user()->paymentAccounts : null,
                'forum_visited' => $request->user() ? $request->user()->has_new_forum_posts : null,
            ],
            'toast' => session('toast'),
            'notification' => session('notification'),
            'locale' => session('locale') ? session('locale') : app()->getLocale(),
            'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name')->toArray() : 'no permission',
        ];
    }
}
