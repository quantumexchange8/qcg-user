<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\PaymentAccount;
use App\Models\Country;
use App\Services\DropdownOptionService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function index(Request $request): Response
    {
        $countries = Country::select('name_en', 'phone_code')->get();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'countries' => $countries,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();

        // if ($user->isDirty('email')) {
        //     $user->email_verified_at = null;

        //     return redirect()->back()->with('toast', [
        //         'title' => 'Invalid Action',
        //         'type' => 'warning'
        //     ]);
        // }

        $phone_code = $request->phone_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $phone_code = ltrim($phone_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $phone_code)) {
            // Concatenate dial code and phone number
            $phone = '+' . $phone_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone = '+' . $phone;
        }

        $user->update([
            // 'name' => $request->name,
            // 'email' => $request->email,
            'phone' => $phone,
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_profile_success'),
            'type' => 'success'
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->paymentAccounts()->delete();
        $user->tradingAccounts()->delete();
        $user->tradingUsers()->delete();
        $user->transactions()->delete();
        $user->rebateAllocations()->delete();
        $user->rebate_wallet()->delete();
        $user->incentive_wallet()->delete();
        $user->teamHasUser()->delete();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateProfilePhoto(Request $request)
    {
        $user = $request->user();

        if ($request->action == 'upload' && $request->hasFile('profile_image')) {
            $user->clearMediaCollection('profile_image');
            $user->addMedia($request->profile_image)->toMediaCollection('profile_image');
        }

        if ($request->action == 'remove') {
            $user->clearMediaCollection('profile_image');
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_profile_photo_success'),
            'type' => 'success'
        ]);
    }

}
