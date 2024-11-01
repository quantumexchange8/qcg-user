<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\SettingLeverage;
use App\Services\CTraderService;
use App\Services\DropdownOptionService;

class AccountController extends Controller
{
    public function index()
    {
        $account_types = AccountType::select('id', 'name')
                        ->where('show_register', 1)
                        ->get();
        $leverages = SettingLeverage::select('leverage', 'value')->get();

        return Inertia::render('Accounts/Accounts', [
            'account_types' => $account_types,
            'leverages' => $leverages,
        ]);
    }

    public function createTradingAccount(Request $request)
    {
        $user = Auth::user();

        $cTraderService = new CTraderService;

        $conn = $cTraderService->connectionStatus();
        if ($conn['code'] != 0) {
            return back()
                ->with('toast', [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]);
        }

        /*
        $mainPassword = Str::random(8);
        $investorPassword = Str::random(8);
         Mail::to($user->email)->send(new NewMetaAccount($mainPassword, $investorPassword));
        return true; */

        $account_type = $request->account_type;
        $mainPassword = Str::random(8);
        $investorPassword = Str::random(8);
        // $group = 'real\ETIQECNElite_A';
        // $group = Group::where('value', $group)->first()->value('meta_group_name');
        $account_type = AccountType::with('metaGroup')->where('id', $account_type)->get()->value('metaGroup.meta_group_name');

        // $remarks = 'TW Test Trading Group';
        $remarks = $user->remark;
        $ctAccount = (new CTraderService)->createUser($user,  $mainPassword, $investorPassword, $account_type, $request->leverage, $request->account_type, null, null, $remarks);
        //Mail::to($user->email)->send(new NewMetaAccount($ctAccount['login'], $mainPassword, $investorPassword));
        return back()->with('toast', 'Successfully Created Trading Account');
        // return true;
    }

    public function getTradingAccounts(Request $request)
    {
        $user = Auth::user();
        // $cTraderService = new CTraderService;

        // $conn = $cTraderService->connectionStatus();
        // if ($conn['code'] != 0) {
        //     return back()
        //         ->with('toast', [
        //             'title' => 'Connection Error',
        //             'type' => 'error'
        //         ]);
        // }

        // try {
        //     $cTraderService->getUserInfo($request->meta_login);
        // } catch (\Throwable $e) {
        //     Log::error($e->getMessage());

        //     return back()
        //         ->with('toast', [
        //             'title' => 'No Account Found',
        //             'type' => 'error'
        //         ]);
        // }

        // Fetch trading accounts based on user ID
        $tradingAccounts = TradingAccount::query()
            ->where('user_id', $user->id)
            ->get() // Fetch the results from the database
            ->map(function($trading_account) {
                return [
                    'id' => $trading_account->id,
                    'meta_login' => $trading_account->meta_login,
                    'account_type' => $trading_account->accountType->name,
                    'balance' => $trading_account->balance,
                    'credit' => $trading_account->credit,
                    'equity' => $trading_account->equity,
                    'leverage' => $trading_account->margin_leverage,
                    // 'account_type_color' => $trading_account->accountType->color,
                    'updated_at' => $trading_account->updated_at,
                ];
            });

        // Return the response as JSON
        return response()->json([
            'tradingAccounts' => $tradingAccounts,
        ]);
    }

    public function getOptions()
    {
        $locale = app()->getLocale();

        $accountOptions = AccountType::whereNot('account_group', 'Demo Account')
            ->where('status', 'active')
            ->get()
            ->map(function ($accountType) use ($locale) {
                $translations = json_decode($accountType->descriptions, true);
                return [
                    'id' => $accountType->id,
                    'name' => $accountType->name,
                    'slug' => $accountType->slug,
                    'account_group' => $accountType->account_group,
                    'leverage' => $accountType->leverage,
                    'descriptions' => $translations[$locale],
                ];
            });

        return response()->json([
            'leverages' => (new DropdownOptionService())->getLeveragesOptions(),
            'transferOptions' => (new DropdownOptionService())->getInternalTransferOptions(),
            'walletOptions' => (new DropdownOptionService())->getWalletOptions(),
            'accountOptions' => $accountOptions,
        ]);
    }
}
