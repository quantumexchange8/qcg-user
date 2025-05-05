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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\Wallet;
use App\Services\CTraderService;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create($referral = null): Response
    {
        $countries = Country::select('name', 'phone_code')->get();

        return Inertia::render('Auth/Register', [
            'referral_code' => $referral,
            'countries' => $countries,
        ]);
    }

    public function firstStep(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255|unique:' . User::class,
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone_code' => 'required',
            'phone' => 'required|regex:/^[0-9]+$/|min:8|unique:' . User::class,
        ];

        $attributes = [
            'first_name' => trans('public.full_name'),
            'email' => trans('public.email'),
            'phone_code' => trans('public.phone_code'),
            'phone' => trans('public.phone_number'),
        ];

        $phone_code = $request->phone_code;
        $phone = $request->phone;
        $phone_number = $request->phone_number;

        // $validator = Validator::make($request->all(), $rules);
        // $validator->setAttributeNames($attributes);

        // $validator->validate();
        if ($request->form_step == 1) {
            $validator = Validator::make($request->all(), $rules);
            $validator->setAttributeNames($attributes);

            $validator->validate();
        } elseif ($request->form_step == 2) {
            // Validation rules for step 2
            $additionalRules = [
                'password' => ['required', 'confirmed', Password::min(8)->letters()->symbols()->numbers()->mixedCase()],
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

        return back();
        // return to_route('sign_up');
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
            'kyc_verification' => ['required', 'array', 'size:2'],
            'kyc_verification.* ' => ['required', 'mimes:jpg,png', 'max:10000'],
        ];

        // Set additional attributes
        $attributes = [
            'kyc_verification' => trans('public.kyc_verification'),
            'kyc_verification.*' => trans('public.kyc_verification_file'),
        ];

        // Create a new validator with updated rules and attributes
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        // Validate the request
        $validator->validate();

        $userData = [
            'first_name' => $request->first_name,
            'chinese_name' => $request->chinese_name ?? null,
            'email' => $request->email,
            'country' => $request->country,
            'dial_code' => $request->phone_code,
            'phone' => $request->phone,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'kyc_approval' => 'pending',
            // 'remark' => 'TW Test Trading Group',
        ];

        Log::info($userData['email']);
        Log::info($userData['first_name']);
        // $default_agent_id = User::where('id_number', 'AID00082')->first()->id;

        $check_referral_code = null;
        $default_upline = null;
        if ($request->referral_code) {
            $referral_code = $request->input('referral_code');
            $check_referral_code = User::where('referral_code', $referral_code)->first();

            if ($check_referral_code) {
                $upline_id = $check_referral_code->id;
                $hierarchyList = empty($check_referral_code['hierarchyList']) ? "-" . $upline_id . "-" : $check_referral_code['hierarchyList'] . $upline_id . "-";

                $userData['upline_id'] = $upline_id;
                $userData['hierarchyList'] = $hierarchyList;
                // $userData['role'] = $upline_id == $default_agent_id ? 'agent' : 'member';
                $userData['role'] = 'member';
            }
        } else {
            $default_upline = User::find(2707);
            $default_upline_id = $default_upline->id;
            $newHierarchyList = empty($default_upline->hierarchyList) ? "-" . $default_upline_id . "-" : $default_upline->hierarchyList . $default_upline_id . "-";

            $userData['upline_id'] = $default_upline_id;
            $userData['hierarchyList'] = $newHierarchyList;
            $userData['role'] = 'member';
        }

        $user = User::create($userData);

        foreach ($request->file('kyc_verification') as $file) {
            $user->addMedia($file)->toMediaCollection('kyc_verification'); 
        }

        $user->setReferralId();

        $id_no = ($user->role == 'agent' ? 'AID' : 'MID') . Str::padLeft($user->id, 5, "0");
        $user->id_number = $id_no;
        $user->save();

        if($user->role == 'agent'){
            $user->assignRole('agent');
        }
        else{
            $user->assignRole('member');
        }

        if ($check_referral_code && $check_referral_code->teamHasUser) {
            $user->assignedTeam($check_referral_code->teamHasUser->team_id);
        }
        else if ($default_upline && $default_upline->teamHasUser) {
            $user->assignedTeam($default_upline->teamHasUser->team_id);
        }

        // create ct id to link ctrader account
        if (App::environment('production')) {
            $ctUser = (new CTraderService)->CreateCTID($user->email);
            $user->ct_user_id = $ctUser['userId'];
            $user->save();
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
        // return redirect('/login')->with('toast', 'Successfully Created Account');
    }

}
