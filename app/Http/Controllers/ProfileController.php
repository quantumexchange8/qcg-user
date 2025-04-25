<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Country;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Services\DropdownOptionService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function index(Request $request): Response
    {
        $countries = Country::select('name', 'phone_code')->get();

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
        $user = $request->user();

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;

            return redirect()->back()->with('toast', [
                'title' => 'Invalid Action',
                'type' => 'warning'
            ]);
        }

        $dial_code = $request->dial_code;

        $user->update([
            'first_name' => $request->first_name,
            'chinese_name' => $request->chinese_name,
            'dial_code' =>  $request->dial_code,
            'phone' => $request->phone,
            'phone_number' => $request->phone_number,
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

        // Get all interactions for the user
        $interactions = $user->interactions;

        // Calculate adjustments for likes and dislikes
        $userLikes = $interactions->where('type', 'like')->count();
        $userDislikes = $interactions->where('type', 'dislike')->count();

        // Get all posts interacted by the user
        $posts = $interactions->pluck('post_id')->unique();

        // Update post counts for each interacted post
        foreach ($posts as $postId) {
            $post = ForumPost::find($postId);

            if ($post) { 
                $post->update([
                    'total_likes_count' => $post->total_likes_count - $userLikes,
                    'total_dislikes_count' => $post->total_dislikes_count - $userDislikes,
                ]);
            }
        }
        
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

    public function updateCryptoWalletInfo(Request $request)
    {
        $wallet_names = $request->wallet_name;
        $token_addresses = $request->token_address;

        $errors = [];

        // Validate wallets and addresses
        foreach ($wallet_names as $index => $wallet_name) {
            $token_address = $token_addresses[$index] ?? '';

            if (empty($wallet_name) && !empty($token_address)) {
                $errors["wallet_name.$index"] = trans('validation.required', ['attribute' => trans('public.wallet_name') . ' #' . ($index + 1)]);
            }

            if (!empty($wallet_name) && empty($token_address)) {
                $errors["token_address.$index"] = trans('validation.required', ['attribute' => trans('public.token_address') . ' #' . ($index + 1)]);
            }
        }

        foreach ($token_addresses as $index => $token_address) {
            $wallet_name = $wallet_names[$index] ?? '';

            if (empty($token_address) && !empty($wallet_name)) {
                $errors["token_address.$index"] = trans('validation.required', ['attribute' => trans('public.token_address') . ' #' . ($index + 1)]);
            }

            if (!empty($token_address) && empty($wallet_name)) {
                $errors["wallet_name.$index"] = trans('validation.required', ['attribute' => trans('public.wallet_name') . ' #' . ($index + 1)]);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        if ($wallet_names && $token_addresses) {
            foreach ($wallet_names as $index => $wallet_name) {
                // Skip iteration if id or token_address is null
                if (is_null($token_addresses[$index])) {
                    continue;
                }

                $conditions = [
                    'user_id' => $request->user_id,
                ];

                // Check if 'id' is set and valid
                if (!empty($request->id[$index])) {
                    $conditions['id'] = $request->id[$index];
                } else {
                    $conditions['id'] = 0;
                }

                PaymentAccount::updateOrCreate(
                    $conditions,
                    [
                        'user_id' => $request->user_id,
                        'payment_account_name' => $wallet_name,
                        'payment_platform' => 'crypto',
                        'payment_platform_name' => 'USDT (TRC20)',
                        'account_no' => $token_addresses[$index],
                        'currency' => 'USDT'
                    ]
                );
            }
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_crypto_wallet_success'),
            'type' => 'success'
        ]);
    }

    public function getKycVerification()
    {
        return response()->json([
            'kycVerification' => Auth::user()->getMedia('kyc_verification'),
        ]);
    }

    public function updateKyc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kyc_verification' => ['required',  'array', 'size:2'],
            'kyc_verification.* ' => ['required', 'mimes:jpg,png', 'max:10000'],
        ])->setAttributeNames([
            'kyc_verification' => trans('public.kyc_verification'),
            'kyc_verification.*' => trans('public.kyc_verification_file'),
        ]);
        $validator->validate();

        $user = $request->user();

        if ($request->hasFile('kyc_verification')) {
            $user->clearMediaCollection('kyc_verification');
            foreach ($request->file('kyc_verification') as $image) {
                $user->addMedia($image)->toMediaCollection('kyc_verification');
            }

            $user->kyc_approval = 'pending';
            $user->save();
        }

        return redirect()->back()->with('toast', [
            'title' => trans("public.toast_update_kyc_success"),
            'type' => 'success',
        ]);
    }
}
