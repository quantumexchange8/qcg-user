<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\ForumPost;
use App\Models\Transaction;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use App\Models\UserPostInteraction;
use App\Models\TradeBrokerHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Dashboard');
    }

    public function getDashboardData()
    {
        $user = Auth::user();
        $groupIds = $user->role === 'agent' ? $user->getChildrenIds() : [];
        $groupIds[] = $user->id;

        $rebate_wallet = $user->rebate_wallet;

        $group_total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'balance_in'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->sum('transaction_amount');

        $group_total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'balance_out'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->sum('amount');

        $group_total_net_balance = TradingAccount::whereIn('user_id', $groupIds)
            ->sum('balance');

        $group_total_asset = TradingAccount::whereIn('user_id', $groupIds)
            ->sum('equity');

        $group_total_trade_lots = TradeBrokerHistory::with('trading_account.ofUser')
            ->whereHas('trading_account.ofUser', function($query) use ($groupIds) {
                $query->whereIn('id', $groupIds); 
            })
            ->sum('trade_lots');

        $group_total_trade_volume = TradeBrokerHistory::with('trading_account.ofUser')
            ->whereHas('trading_account.ofUser', function($query) use ($groupIds) {
                $query->whereIn('id', $groupIds); 
            })
            ->sum('trade_volume');

        return response()->json([
            'rebateWallet' => $rebate_wallet,
            'groupTotalDeposit' => $group_total_deposit,
            'groupTotalWithdrawal' => $group_total_withdrawal,
            // 'groupTotalNetBalance' => $group_total_deposit - $group_total_withdrawal,
            'groupTotalNetBalance' => $group_total_net_balance,
            'groupTotalAsset' => $group_total_asset,
            'groupTotalTradeLot' => $group_total_trade_lots,
            'groupTotalTradeVolume' => $group_total_trade_volume,
        ]);
    }

    public function getRebateEarnData()
    {
        $user = Auth::user();

        return response()->json([
            'rebateEarn' => $user->rebate_amount,
        ]);
    }

    public function admin_login(Request $request, $hashedToken)
    {
        $users = User::all();

        foreach ($users as $user) {

            $dataToHash = md5($user->first_name . $user->email . $user->id_number);
            if ($dataToHash === $hashedToken) {
                $admin_id = $request->admin_id;
                $admin_name = $request->admin_name;

                Activity::create([
                    'log_name' => 'access_portal',
                    'description' => $admin_name . ' with ID: ' . $admin_id . ' has access user ' . $user->first_name . ' with ID: ' . $user->id ,
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'causer_type' => User::class,
                    'causer_id' => $admin_id,
                    'event' => 'access_portal',
                ]);

                Auth::login($user);
                return redirect()->route('dashboard');
            }
        }

        return redirect()->route('login')->with('toast', [
            'title' => trans('public.access_denied'),
            'type' => 'error'
        ]);
    }
}
