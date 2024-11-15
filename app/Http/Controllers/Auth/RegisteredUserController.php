<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Country;
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
//use App\Http\Requests\RegisterRequest;
//use App\Notifications\OtpNotification;
use App\Models\Wallet;
use App\Services\CTraderService;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $countries = Country::select('name_en', 'phone_code')->get();

        return Inertia::render('Auth/Register', [
            'countries' => $countries,
        ]);
    }

    public function firstStep(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255|unique:' . User::class,
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone_code' => 'required',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|unique:' . User::class,
        ];

        $attributes = [
            'first_name' => trans('public.full_name'),
            'email' => trans('public.email'),
            'phone_code' => trans('public.phone_code'),
            'phone' => trans('public.phone_number'),
        ];

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

        // Merge the modified phone number back into the request
        $request->merge(['phone' => $phone]);

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);


        $validator->validate();
        // } elseif ($request->form_step == 2) {
        //     // Validation rules for step 2
        //     $additionalRules = [
        //         'password' => ['required', 'confirmed', Password::min(6)->letters()->symbols()->numbers()->mixedCase()],
        //     ];

        //     // Merge additional rules with existing rules
        //     $rules = array_merge($rules, $additionalRules);

        //     // Set additional attributes
        //     $additionalAttributes = [
        //         'password' => trans('public.password'),
        //     ];

        //     // Merge additional attributes with existing attributes
        //     $attributes = array_merge($attributes, $additionalAttributes);

        //     // Create a new validator with updated rules and attributes
        //     $validator = Validator::make($request->all(), $rules);
        //     $validator->setAttributeNames($attributes);

        //     // Validate the request
        //     $validator->validate();
        // }

        return to_route('sign_up');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $conn = (new CTraderService)->connectionStatus();

        if ($conn['code'] != 0) {
            return back()->with([
                'toast' => [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]
            ]);
        }

        // if ($conn['code'] != 0) {
        //     if ($conn['code'] == 10) {
        //         return back()->with('error_message', 'No connection with cTrader Server');
        //     }
        //     return back()->with('error_message', $conn['message']);
        // }

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
        // Validation rules for step 2
        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)->letters()->symbols()->numbers()->mixedCase()],
        ];

        // Set additional attributes
        $attributes = [
            'password' => trans('public.password'),
        ];

        // Create a new validator with updated rules and attributes
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        // Validate the request
        $validator->validate();

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

        $ctUser = (new CTraderService)->CreateCTID($request->email);

        $userData = [
            'first_name' => $request->first_name,
            'email' => $request->email,
            'country' => $request->country,
            'phone' => $phone,
            'password' => Hash::make($request->password),
            'ct_user_id' => $ctUser['userId'],
            'remarks' => 'TW Test Trading Group',
        ];


        $user = User::create($userData);

        $user->setReferralId();

        event(new Registered($user));

        return redirect('/login')->with('toast', 'Successfully Created Account');
    }

}
