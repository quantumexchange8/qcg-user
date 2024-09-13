<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use App\Notifications\OtpNotification;
use App\Models\Wallet;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function firstStep(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:' . User::class,
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|unique:' . User::class,
        ];

        $attributes = [
            'name' => trans('public.full_name'),
            'email' => trans('public.email'),
            'phone' => trans('public.phone_number'),
            
        ];

        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone = '+' . $phone;
        }

        // Merge the modified phone number back into the request
        $request->merge(['phone' => $phone]);

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        if ($request->form_step == 1) {
            $validator->validate();
        } elseif ($request->form_step == 2) {
            // Validation rules for step 2
            $additionalRules = [
                'password' => ['required', 'confirmed', Password::min(6)->letters()->symbols()->numbers()->mixedCase()],
            ];

            // Merge additional rules with existing rules
            $rules = array_merge($rules, $additionalRules);

            // Set additional attributes
            $additionalAttributes = [
                'password' => trans('public.password'),
            ];

            // Merge additional attributes with existing attributes
            $attributes = array_merge($attributes, $additionalAttributes);

            // Create a new validator with updated rules and attributes
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attributes);

            // Validate the request
            $validator->validate();
        }

        return to_route('register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
//        $otp = VerifyOtp::where('email', $request->email)->first();
//
//        $expirationTime = Carbon::parse($otp->updated_at)->addMinutes(5);
//
//        if (Carbon::now()->greaterThan($expirationTime)) {
//            throw ValidationException::withMessages([
//                'verification_code' => 'The Verification Code expired.'
//            ]);
//        }
//
//        if($otp->otp != $request->verification_code){
//            throw ValidationException::withMessages(['verification_code' => 'Invalid Verification Code']);
//        }

        $dial_code = $request->dial_code;
        $phone = $request->phone;

        // Remove leading '+' from dial code if present
        $dial_code = ltrim($dial_code, '+');

        // Remove leading '+' from phone number if present
        $phone = ltrim($phone, '+');

        // Check if phone number already starts with dial code
        if (!str_starts_with($phone, $dial_code)) {
            // Concatenate dial code and phone number
            $phone = '+' . $dial_code . $phone;
        } else {
            // If phone number already starts with dial code, use the phone number directly
            $phone = '+' . $phone;
        }

        $userData = [
            'first_name' => $request->name,
            'email' => $request->email,
            // 'country' => $request->country,
            // 'nationality' => $request->nationality,
            'phone' => $phone,
            // 'dob' => $dob,
            'password' => Hash::make($request->password),
            // 'role' => 'user',
            // 'top_leader_id' => null,
        ];

        // if ($request->referral_code) {
        //     $referral_code = $request->input('referral_code');
        //     $check_referral_code = User::where('referral_code', $referral_code)->first();

        //     if ($check_referral_code) {
        //         $upline_id = $check_referral_code->id;
        //         $top_leader_id = $check_referral_code->top_leader_id ? $check_referral_code->top_leader_id : $check_referral_code->id;
        //         $hierarchyList = empty($check_referral_code['hierarchyList']) ? "-" . $upline_id . "-" : $check_referral_code['hierarchyList'] . $upline_id . "-";

        //         $userData['top_leader_id'] = $top_leader_id;
        //         $userData['upline_id'] = $upline_id;
        //         $userData['hierarchyList'] = $hierarchyList;
        //         $userData['is_public'] = $check_referral_code->is_public;

        //         if ($userData['top_leader_id'] != 7) {
        //             $userData['rank_up_status'] = 'manual';
        //         }
        //     }
        // }

        $user = User::create($userData);

        // $user->setReferralId();

        event(new Registered($user));

        return redirect()->route('login')
            ->with('title', trans('public.success_registration'))
            ->with('success', trans('public.successfully_registration'));
    }

}
