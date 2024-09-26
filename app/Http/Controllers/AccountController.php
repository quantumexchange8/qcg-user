<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\SettingLeverage;

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

    public function createTradingAccounts(Request $request)
    {

    }

    public function getTradingAccounts(Request $request)
    {
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
            ->where('user_id', $request->id)
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
}
