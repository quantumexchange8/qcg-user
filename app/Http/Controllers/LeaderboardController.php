<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Models\PaymentAccount;
use App\Services\RunningNumberService;
use App\Models\LeaderboardBonus;
use App\Models\LeaderboardProfile;
use App\Models\TradeBrokerHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeaderboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Leaderboard/Leaderboard');
    }

    public function getTotalIncentiveGraph(Request $request)
    {
        $user_id = Auth::id();
        $incentiveYear = $request->query('year', Carbon::now()->year); 

        $monthlyIncentives = LeaderboardBonus::selectRaw('MONTH(created_at) as month, SUM(incentive_amount) as total')
            ->where('user_id', $user_id)
            ->whereYear('created_at', $incentiveYear)
            ->whereIn('id', function($query) use ($user_id, $incentiveYear) {
                $query->selectRaw('MAX(id)')
                    ->from('leaderboard_bonuses')
                    ->where('user_id', $user_id)
                    ->whereYear('created_at', $incentiveYear)
                    ->groupBy('leaderboard_profile_id', \DB::raw('MONTH(created_at)'));
            })
            ->groupBy(\DB::raw('MONTH(created_at)'))
            ->orderBy(\DB::raw('MONTH(created_at)'))
            ->get();

        $totalYearlyIncentive = $monthlyIncentives->sum('total');

        return response()->json([
            'totalYearlyIncentive' => $totalYearlyIncentive,   
            'monthlyIncentives' => $monthlyIncentives     
        ]); 
    }

    public function getIncentiveData()
    {
        $user = Auth::user();

        $incentive_wallet = $user->incentive_wallet;

        return response()->json([
            'incentiveWallet' => $incentive_wallet,
        ]);
    }

    public function incentiveWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => ['required', 'exists:wallets,id'],
            'amount' => ['required', 'numeric', 'gte:1'],
            'wallet_address' => ['required']
        ])->setAttributeNames([
            'wallet_id' => trans('public.wallet'),
            'amount' => trans('public.amount'),
            'wallet_address' => trans('public.receiving_wallet'),
        ]);
        $validator->validate();

        $amount = $request->amount;
        $wallet = Wallet::find($request->wallet_id);

        if ($amount > 0) {
            $paymentWallet = PaymentAccount::where('user_id', Auth::id())
             ->where('account_no', $request->wallet_address)
             ->first();

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'category' => 'incentive_wallet',
                'transaction_type' => 'withdrawal',
                'from_wallet_id' => $request->wallet_id,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'payment_account_id' => $paymentWallet->id,
                'to_wallet_address' => $paymentWallet->account_no,
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'processing',
                'old_wallet_amount' => $wallet->balance,
                'new_wallet_amount' => $wallet->balance -= $amount,
            ]);

            $wallet->save();
            // Set notification data in the session
            return redirect()->back()->with('notification', [
                'details' => $transaction,
                'type' => 'withdrawal',
                // 'withdrawal_type' => 'rebate' this not put show meta_login put rebate show Rebate put bonus show Bonus
            ]);
        } 
        else {
            return back()->with('toast', [
                'title' => trans("public.unable_to_withdraw_incentive"),
                'message' => trans("public.toast_withdraw_incentive_error"),
                'type' => 'error',
            ]);
        }

    }


    public function getWithdrawalHistory(Request $request)
    {
        $id = Auth::id();

        $query = Transaction::where('user_id', $id)->where('transaction_type', 'withdrawal')->where('category', 'incentive_wallet');

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $withdrawals = $query
            ->get()
            ->map(function ($withdrawal) {
                return [
                    'id' => $withdrawal->id,
                    'created_at' => $withdrawal->created_at,
                    'transaction_number' => $withdrawal->transaction_number,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'from_wallet_id' => $withdrawal->from_wallet_id,
                    'to_wallet_address' => $withdrawal->to_wallet_address,
                    'remarks' => $withdrawal->remarks,
                    'wallet_name' => $withdrawal->payment_account->payment_account_name ?? '-',
                    'wallet_address' => $withdrawal->payment_account->account_no ?? '-',
                ];
            });

        return response()->json([
            'withdrawals' => $withdrawals,
            'totalWithdrawalAmount' => $query->sum('amount'),
        ]);  
    }


    public function getAchievements(Request $request)
    {
        $user_id = Auth::id();
        $bonusQuery = LeaderboardProfile::query()->where('user_id', $user_id);

        $profiles = $bonusQuery->get();

        $formattedProfiles = $profiles->map(function($profile) {
            $incentive_amount = 0;
            $achieved_percentage = 0;
            $achieved_amount = 0;

            $today = Carbon::today();

            $useLastPayoutDate = $profile->created_at->eq($profile->last_payout_date);

            // Set start and end dates based on calculation period
            if ($profile->calculation_period == 'every_sunday') {
                // Start of the current week (Monday) and end of the current week (Sunday)
                $startDate = $today->copy()->startOfWeek();
                $endDate = $today->copy()->endOfWeek();
            } elseif ($profile->calculation_period == 'every_second_sunday') {
                // Start of the month
                $startDate = $today->copy()->startOfMonth();

                // Find the first Sunday of the month
                $firstSunday = $startDate->copy()->next('Sunday');

                // Find the second Sunday of the month
                $secondSunday = $firstSunday->copy()->addWeek();

                // If today is before or on the second Sunday, calculate until the day before the second Sunday
                if ($today->lessThan($secondSunday)) {
                    $endDate = $secondSunday->copy()->subDay()->endOfDay();
                } else {
                    // If today is after the second Sunday, set startDate to the second Sunday
                    $startDate = $secondSunday->copy();
                    $endDate = $today->copy()->endOfWeek(); // Or end of current week if needed
                }

            } elseif ($profile->calculation_period == 'first_sunday_of_every_month') {
                $startDate = $today->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } else {
                // Default to the entire current month if no calculation period is specified
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
            }

            if ($useLastPayoutDate) {
                $startDate = $profile->last_payout_date->copy()->startOfDay();
            }

            if ($profile->sales_calculation_mode == 'personal_sales') {
                if ($profile->sales_category == 'gross_deposit') {
                    $gross_deposit = Transaction::where('user_id', $profile->user_id)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $achieved_percentage = ($gross_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($gross_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $gross_deposit;
                } elseif ($profile->sales_category == 'net_deposit') {
                    $total_deposit = Transaction::where('user_id', $profile->user_id)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $total_withdrawal = Transaction::where('user_id', $profile->user_id)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'withdrawal')
                                ->orWhere('transaction_type', 'balance_out')
                                ->orWhere('transaction_type', 'rebate_out');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $net_deposit = $total_deposit - $total_withdrawal;

                    $achieved_percentage = ($net_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($net_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $net_deposit;
                } elseif ($profile->sales_category == 'trade_volume') {
                    $meta_logins = $profile->user->tradingAccounts->pluck('meta_login');

                    $trade_volume = TradeBrokerHistory::whereIn('meta_login', $meta_logins)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('trade_lots');

                    $achieved_percentage = ($trade_volume / $profile->target_amount) * 100;
                    $incentive_amount = $achieved_percentage >= $profile->calculation_threshold ? $profile->incentive_rate : 0;
                    $achieved_amount = $trade_volume;
                }
            } elseif ($profile->sales_calculation_mode == 'group_sales') {
                $child_ids = $profile->user->getChildrenIds();
                $child_ids[] = $profile->user_id;

                if ($profile->sales_category == 'gross_deposit') {
                    $gross_deposit = Transaction::whereIn('user_id', $child_ids)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $achieved_percentage = ($gross_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($gross_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $gross_deposit;
                } elseif ($profile->sales_category == 'net_deposit') {
                    $total_deposit = Transaction::whereIn('user_id', $child_ids)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $total_withdrawal = Transaction::whereIn('user_id', $child_ids)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'withdrawal')
                                ->orWhere('transaction_type', 'balance_out')
                                ->orWhere('transaction_type', 'rebate_out');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $net_deposit = $total_deposit - $total_withdrawal;

                    $achieved_percentage = ($net_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($net_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $net_deposit;
                } elseif ($profile->sales_category == 'trade_volume') {
                    $meta_logins = TradingAccount::whereIn('user_id', $child_ids)
                        ->get()
                        ->pluck('meta_login')
                        ->toArray();

                    $trade_volume = TradeBrokerHistory::whereIn('meta_login', $meta_logins)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('trade_lots');

                    $achieved_percentage = ($trade_volume / $profile->target_amount) * 100;
                    $incentive_amount = $achieved_percentage >= $profile->calculation_threshold ? $profile->incentive_rate : 0;
                    $achieved_amount = $trade_volume;
                }
            }

            return [
                'id' => $profile->id,
                'user_id' => $profile->user->id,
                'name' => $profile->user->first_name,
                'email' => $profile->user->email,
                'sales_calculation_mode' => $profile->sales_calculation_mode == 'personal_sales' ? 'personal' : 'group',
                'sales_category' => $profile->sales_category,
                'target_amount' => $profile->target_amount,
                'incentive_amount' => $incentive_amount,
                'incentive_rate' => $profile->incentive_rate,
                'calculation_threshold' => intval($profile->calculation_threshold),
                'achieved_percentage' => $achieved_percentage,
                'achieved_amount' => $achieved_amount,
                'calculation_period' => $profile->calculation_period,
                'last_payout_date' => $profile->last_payout_date,
                'next_payout_date' => $profile->next_payout_date,
            ];
        });

        // Sort the formatted profiles based on the category provided in the request
        $sortCategory = $request->category; // This should be either "incentive_amount" or "achieved_percentage"
        if (in_array($sortCategory, ['incentive_amount', 'achieved_percentage'])) {
            $formattedProfiles = $formattedProfiles->sortByDesc($sortCategory);
        }

        return response()->json([
            'incentiveProfiles' => $formattedProfiles,
            // 'totalRecords' => $totalRecords,
            // 'currentPage' => $profiles->currentPage(),
        ]);
    }

    public function getAgents()
    {
        $users = User::where('role', 'agent')
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                ];
            });

        return response()->json([
            'users' => $users,
        ]);
    }

    public function getStatementData(Request $request)
    {
        $bonusQuery = LeaderboardBonus::where('leaderboard_profile_id', $request->profile_id);

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $bonusQuery->whereBetween('created_at', [$start_date, $end_date]);
        }

        $bonuses = $bonusQuery
            ->get()
            ->map(function ($bonus) {
                return [
                    'id' => $bonus->id,
                    'target_amount' => $bonus->target_amount,
                    'achieved_amount' => $bonus->achieved_amount,
                    'incentive_rate' => $bonus->incentive_rate,
                    'incentive_amount' => $bonus->incentive_amount,
                    'created_at' => $bonus->created_at,
                ];
            });

        return response()->json([
            'bonuses' => $bonuses,
            'totalBonusAmount' => $bonusQuery->sum('incentive_amount'),
        ]);        
    }
}
