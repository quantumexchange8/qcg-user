<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use App\Notifications\DepositApprovalNotification;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\AccountType;
use App\Models\User;
use App\Models\TradingUser;
use App\Models\PaymentAccount;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\SettingLeverage;
use App\Models\Wallet;
use App\Models\UserAccountTypeVisibility;
use App\Models\SettingAutoApproval;
use App\Services\CTraderService;
use App\Services\RunningNumberService;
use App\Services\DropdownOptionService;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    // public function index()
    // {
    //     $account_types = AccountType::select('id', 'name')
    //                     ->where('show_register', 1)
    //                     ->get();
    //     $leverages = SettingLeverage::select('leverage', 'value')->get();

    //     return Inertia::render('Accounts/Account', [
    //         // 'account_types' => $account_types,
    //         // 'leverages' => $leverages,
    //     ]);
    // }

    public function getOptions()
    {
        $locale = app()->getLocale();

        $accountOptions = AccountType::whereNot('account_group', 'Demo Account')
            ->where('status', 'active')
            ->get()
            ->map(function ($accountType) use ($locale) {
                $translations = json_decode($accountType->descriptions, true);

                if ($accountType->visible_to === 'selected_members') {

                    $userHasVisibility = UserAccountTypeVisibility::where('account_type_id', $accountType->id)
                        ->where('user_id', Auth::id())
                        ->exists();

                    if (!$userHasVisibility) {
                        return null;
                    }
                }
                return [
                    'id' => $accountType->id,
                    'name' => $accountType->name,
                    'slug' => $accountType->slug,
                    'account_group' => $accountType->account_group,
                    'leverage' => $accountType->leverage,
                    'descriptions' => $translations,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'leverages' => (new DropdownOptionService())->getLeveragesOptions(),
            'transferOptions' => (new DropdownOptionService())->getInternalTransferOptions(),
            'walletOptions' => (new DropdownOptionService())->getWalletOptions(),
            'accountOptions' => $accountOptions,
        ]);
    }

    public function create_live_account(Request $request)
    {
        Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'accountType' => 'required|exists:account_types,account_group',
            'leverage' => 'required|integer|min:1',
        ])->setAttributeNames([
            'accountType' => trans('public.account_type'),
            'leverage' => trans('public.leverage'),
        ])->validate();

        $user = User::find($request->user_id);

        // Only create ct_user_id if it is null
        if ($user->ct_user_id === null) {
            // Create CT ID to link ctrader account
            $ctUser = (new CTraderService)->CreateCTID($user->email);
            $user->ct_user_id = $ctUser['userId'];
            $user->save();
        }

        // Retrieve the account type by account_group
        $accountType = AccountType::where('account_group', $request->accountType)->first();

        // Check the number of existing trading accounts for this user and account type
        $existingAccountsCount = TradingAccount::where('user_id', $user->id)
            ->where('account_type_id', $accountType->id)
            ->count();

        // Check if the user has reached the maximum number of accounts
        if ($existingAccountsCount >= $accountType->maximum_account_number) {
            return back()->with('toast', [
                'title' => trans("public.account_limit_reached"),
                'message' => trans("public.account_limit_reached_message"),
                'type' => 'warning',
            ]);
        }

        if (App::environment('production')) {
            $mainPassword = Str::random(8);
            $investorPassword = Str::random(8);
            (new CTraderService)->createUser($user,  $mainPassword, $investorPassword, $accountType->account_group, $request->leverage, $accountType, null, null, '');
        }

        return back()->with('toast', [
            'title' => trans("public.toast_open_live_account_success"),
            'type' => 'success',
        ]);
    }

    public function create_demo_account(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'leverage' => 'required|integer|min:1',
        ]);

        $user = User::find($request->user_id);

        // Only create ct_user_id if it is null
        if ($user->ct_user_id === null) {
            // Create CT ID to link ctrader account
            $ctUser = (new CTraderService)->CreateCTID($user->email);
            $user->ct_user_id = $ctUser['userId'];
            $user->save();
        }

        $accountType = AccountType::where('account_group', 'Demo Account')->first();

        if (App::environment('production')) {
            $mainPassword = Str::random(8);
            $investorPassword = Str::random(8);
            (new CTraderService)->createDemoUser($user,  $mainPassword, $investorPassword, $accountType->account_group, $request->leverage, $accountType->id, null, null, '', $request->amount);
        }

        return back()->with('toast', [
            'title' => trans("public.toast_open_demo_account_success"),
            'type' => 'success',
        ]);
    }

    public function getLiveAccount(Request $request)
    {
        $user = Auth::user();
        $accountType = $request->input('accountType');

        $conn = (new CTraderService)->connectionStatus();
        if ($conn['code'] != 0) {
            return back()
                ->with('toast', [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]);
        }

        $trading_accounts = $user->tradingAccounts()
            ->whereHas('accountType', function($q) use ($accountType) {
                $q->where('category', $accountType);
            })
            ->get();
        // (new CTraderService)->getGroups();
        try {
            foreach ($trading_accounts as $trading_account) {
                (new CTraderService)->getUserInfo($trading_account->meta_login);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

        $liveAccounts = TradingAccount::with('accountType', 'transactions', 'trade_history')
            ->where('user_id', $user->id)
            ->when($accountType, function ($query) use ($accountType) {
                return $query->whereHas('accountType', function ($query) use ($accountType) {
                    $query->where('category', $accountType);
                });
            })
            ->get()
            ->map(function ($account) {
                // $achievedAmount = 0;
                // $claimAmount = 0;
                $expiryDate = null;
                $daysLeft = 0;
                $claimable_status = false;
                $bonus_status = null;

                if($account->accountType->category === 'promotion') {
                    if ($account->promotion_period_type === 'specific_date_range') {
                        $expiryDate = Carbon::parse($account->promotion_period);
                    } elseif ($account->promotion_period_type === 'from_account_opening') {
                        $expiryDate = Carbon::parse($account->created_at)
                            ->addDays((int) $account->promotion_period);
                    }

                    if ($expiryDate) {
                        $now = Carbon::now();
                        $daysLeft = intval($expiryDate->greaterThan($now)
                            ? $now->diffInDays($expiryDate)
                            : -$expiryDate->diffInDays($now));
                    }

                    if ($account->claimable_amount > 0 && $account->is_claimed == 'claimable') {
                        $claimable_status = true;
                    }

                    if ($account->is_claimed) {
                        $bonus_status = $account->is_claimed;
                    }
                    else {
                        $bonus_status = 'claimable';
                    }
                }

                return [
                    'id' => $account->id,
                    'user_id' => $account->user_id,
                    'meta_login' => $account->meta_login,
                    'balance' => $account->balance - $account->credit,
                    'credit' => $account->credit,
                    'leverage' => $account->margin_leverage,
                    'equity' => $account->equity,
                    'account_type' => $account->accountType->slug,
                    'account_type_leverage' => $account->accountType->leverage,
                    'account_type_color' => $account->accountType->color,
                    'account_category' => $account->accountType->category,
                    'is_active' => $account->status,
                    'promotion_title' => $account->promotion_title,
                    'promotion_description' => $account->promotion_description,
                    'promotion_period_type' => $account->promotion_period_type,
                    'promotion_period' => $account->promotion_period,
                    'promotion_type' => $account->promotion_type,
                    'bonus_type' => $account->bonus_type,
                    'bonus_amount_type' => $account->bonus_amount_type,
                    'bonus_amount' => $account->bonus_amount,
                    'target_amount' => $account->target_amount ?? $account->min_threshold,
                    'applicable_deposit' => $account->applicable_deposit,
                    'credit_withdraw_policy' => $account->credit_withdraw_policy,
                    'credit_withdraw_date_period' => $account->credit_withdraw_date_period,
                    'is_claimed' => $bonus_status,
                    'created_at' => $account->created_at,
                    'achieved_amount' => $account->achieved_amount,
                    'expiry_date' => $expiryDate,
                    'days_left' => $daysLeft,
                    'claimable_status' => $claimable_status,
                    'claimable_amount' => $account->claimable_amount,
                ];
            });

        return response()->json($liveAccounts);
    }

    public function claim_bonus(Request $request)
    {
        // $request->validate([
        //     'account_id' => 'required|exists:trading_accounts,id',
        //     'amount' => ['required', 'numeric', 'gte:50'],
        // ]);

        // $conn = (new CTraderService)->connectionStatus();
        // if ($conn['code'] != 0) {
        //     return back()
        //         ->with('toast', [
        //             'title' => 'Connection Error',
        //             'type' => 'error'
        //         ]);
        // }

        Validator::make($request->all(), [
            'account_id' => ['required', 'exists:trading_accounts,id'],
            'amount' => ['required', 'numeric'],
        ])->setAttributeNames([
            'account_id' => trans('public.account'),
            'amount' => trans('public.amount'),
        ])->validate();

        $claim_amount = $request->amount;
        $tradingAccount = TradingAccount::find($request->account_id);
        //  if ($tradingAccount->balance < $amount) {
        //      throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        //  }

        try {
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'category' => 'bonus',
                'transaction_type' => $request->bonus_type,
                'to_meta_login' => $tradingAccount->meta_login,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'amount' => $claim_amount,
                'transaction_charges' => 0,
                'transaction_amount' => $claim_amount,
                'status' => 'processing',
            ]);

            $tradingAccount->update([
                'is_claimed' => 'pending',
            ]);

            return back()->with('notification', [
                'type' => 'bonus',
                'details' => $transaction,
            ]);
        } catch (\Exception $e) {
                return back()->with('toast', [
                'title' => trans('public.toast_claim_bonus_error'),
                'type' => 'error',
            ]);
        }
    }

    public function getAccountReport(Request $request)
    {
        $meta_login = $request->query('meta_login');
        $type = $request->query('type');
        $monthYear = $request->input('selectedMonth');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            $carbonDate = Carbon::createFromFormat('F Y', $monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        $query = Transaction::query()
                ->where('status', 'successful')
                ->whereBetween('created_at', [$startDate, $endDate]);

        if ($meta_login) {
            $query->where(function($subQuery) use ($meta_login) {
                $subQuery->where('from_meta_login', $meta_login)
                    ->orWhere('to_meta_login', $meta_login);
            });
        }

        // Apply type filter
        if ($type && $type !== 'all') {
            // Filter based on specific transaction types directly
            if ($type === 'deposit') {
                $query->where('transaction_type', 'deposit');
            } elseif ($type === 'withdrawal') {
                $query->where('transaction_type', 'withdrawal');
            } elseif ($type === 'transfer') {
                $query->whereIn('transaction_type', ['transfer_to_account', 'account_to_account']);
            }
        }

        $transactions = $query
            ->latest()
            ->get()
            ->map(function ($transaction) {
                return [
                    'category' => $transaction->category,
                    'transaction_type' => $transaction->transaction_type,
                    'from_meta_login' => $transaction->from_meta_login,
                    'to_meta_login' => $transaction->to_meta_login,
                    'transaction_number' => $transaction->transaction_number,
                    'payment_account_id' => $transaction->payment_account_id,
                    'from_wallet_address' => $transaction->from_wallet_address,
                    'to_wallet_address' => $transaction->to_wallet_address,
                    'txn_hash' => $transaction->txn_hash,
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'status' => $transaction->status,
                    'comment' => $transaction->comment,
                    'remarks' => $transaction->remarks,
                    'created_at' => $transaction->created_at,
                    'wallet_name' => $transaction->payment_account->payment_account_name ?? '-',
                    'wallet_type' => $transaction->from_wallet->type ?? '-',
                ];
            });

        return response()->json($transactions);
    }

    public function deposit_to_account(Request $request)
    {
        Validator::make($request->all(), [
            'meta_login' => ['required', 'exists:trading_accounts,meta_login'],
            'amount' => ['required', 'numeric', 'min:1'],
            'checkbox1' => 'accepted',
            'checkbox2' => 'accepted',
        ])->setAttributeNames([
            'meta_login' => trans('public.account_no'),
            'amount' => trans('public.amount'),
            'checkbox1' => trans('public.terms_and_conditions'),
            'checkbox2' => trans('public.terms_and_conditions'),
        ])->validate();

        $user = Auth::user();

        $transaction = Transaction::where([
            'transaction_type' => 'deposit',
            'to_meta_login' => $request->meta_login,
            'status' => 'processing',
            'txn_hash' => null,
        ])
            ->first();

        if (!$transaction) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'category' => 'trading_account',
                'transaction_type' => 'deposit',
                'to_meta_login' => $request->meta_login,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'amount' => $request->amount,
                'status' => 'processing',
            ]);
        } else {
            $transaction->update([
                'amount' => $request->amount,
            ]);
        }

        $payoutSetting = config('payment-gateway');
        $domain = $_SERVER['HTTP_HOST'];

        if ($domain === 'login.qcgexchange.com') {
            $selectedPayout = $payoutSetting['live'];
        } else {
            $selectedPayout = $payoutSetting['staging'];
        }

        $vCode = md5($transaction->amount . $selectedPayout['appId'] . $transaction->transaction_number . $selectedPayout['merchantId'] . $selectedPayout['ttKey']);

        $params = [
            'userName' => $user->first_name,
            'userEmail' => $user->email,
            'orderNumber' => $transaction->transaction_number,
            'userId' => $user->id,
            'amount' => $transaction->amount,
            'merchantId' => $selectedPayout['merchantId'],
            'vCode' => $vCode,
            'locale' => app()->getLocale(),
        ];

        // Send response
        $url = $selectedPayout['paymentUrl'] . '/payment';
        $redirectUrl = $url . "?" . http_build_query($params);

        return Inertia::location($redirectUrl);

        // $tradingAccount = TradingAccount::find($request->account_id);
        // (new CTraderService)->getUserInfo(collect($tradingAccount));

        // $tradingAccount = TradingAccount::find($request->account_id);
        // $amount = $request->input('amount');
        // $wallet = Auth::user()->wallet->first();

        // if ($wallet->balance < $amount) {
        //     throw ValidationException::withMessages(['wallet' => trans('public.insufficient_balance')]);
        // }

        // try {
        //     $trade = (new CTraderService)->createTrade($tradingAccount->meta_login, $amount, $tradingAccount->account_type_id, "Deposit To Account", ChangeTraderBalanceType::DEPOSIT);
        // } catch (\Throwable $e) {
        //     if ($e->getMessage() == "Not found") {
        //         TradingUser::firstWhere('meta_login', $tradingAccount->meta_login)->update(['acc_status' => 'inactive']);
        //     } else {
        //         Log::error($e->getMessage());
        //     }
        //     return response()->json(['success' => false, 'message' => $e->getMessage()]);
        // }

        // $ticket = $trade->getTicket();
        // $newBalance = $wallet->balance - $amount;

//         $transaction = Transaction::create([
//             'user_id' => Auth::id(),
//             'category' => 'trading_account',
//             'transaction_type' => 'fund_in',
//             'from_wallet_id' => $wallet->id,
//             'to_meta_login' => $tradingAccount->meta_login,
//             'transaction_number' => RunningNumberService::getID('transaction'),
//             'amount' => $amount,
//             'transaction_charges' => 0,
//             'transaction_amount' => $amount,
//             'old_wallet_amount' => $wallet->balance,
//             'new_wallet_amount' => $newBalance,
//             'status' => 'processing',
//             'ticket' => $ticket,
//         ]);

        // $wallet->balance = $newBalance;
        // $wallet->save();

        // // Check if the account exists
        // if ($tradingAccount) {
        //     // Redirect back with success message
        //     return back()->with('toast', [
        //         'title' => trans('public.toast_revoke_account_success'),
        //         'type' => 'success',
        //     ]);
        // }

//        $transactionData = [
//            'user_id' => 1,
//            'transaction_number' => 'TX1234567890',
//            'from_meta_login' => '123456',
//            'transaction_amount' => 1000.00,
//            'amount' => 1000.00,
//            'receiving_address' => 'dummy_address',
//            'created_at' => '2024-07-27 16:09:45',
//        ];
//
//        // Set notification data in the session
//        return redirect()->back()->with('notification', [
//            'details' => $transactionData,
//            'type' => 'deposit',
//        ]);

    }

    public function accountWithdrawal(Request $request)
    {
        Validator::make($request->all(), [
            'account_id' => ['required', 'exists:trading_accounts,id'],
            'amount' => ['required', 'numeric', 'gte:50'],
            'wallet_address' => ['required']
        ])->setAttributeNames([
            'account_id' => trans('public.account'),
            'amount' => trans('public.amount'),
            'wallet_address' => trans('public.receiving_wallet'),
        ])->validate();

        $amount = $request->amount;

        // request withdrawal
         $conn = (new CTraderService)->connectionStatus();
         if ($conn['code'] != 0) {
             return back()
                 ->with('toast', [
                     'title' => 'Connection Error',
                     'type' => 'error'
                 ]);
         }

         $tradingAccount = TradingAccount::find($request->account_id);
         (new CTraderService)->getUserInfo($tradingAccount->meta_login);

         if (($tradingAccount->balance - $tradingAccount->credit) < $amount) {
             throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
         }

         try {
            if ($tradingAccount->accountType->category == 'promotion' && $tradingAccount->credit > 0) {
                $credit_amount = $tradingAccount->credit;
                $tradeCredit = (new CTraderService)->createTrade($tradingAccount->meta_login, $credit_amount, "Credit Withdraw From Account", ChangeTraderBalanceType::WITHDRAW_NONWITHDRAWABLE_BONUS);
                $ticketCredit = $tradeCredit->getTicket();
                Transaction::create([
                    'user_id' => Auth::id(),
                    'category' => 'trading_account',
                    'transaction_type' => 'credit_withdrawal',
                    'from_meta_login' => $tradingAccount->meta_login,
                    'ticket' => $ticketCredit,
                    'transaction_number' => RunningNumberService::getID('transaction'),
                    'amount' => $credit_amount,
                    'transaction_charges' => 0,
                    'transaction_amount' => $credit_amount,
                    'status' => 'successful',
                    'comment' => 'Credit Withdrawal'
                ]);
            }

            $trade = (new CTraderService)->createTrade($tradingAccount->meta_login, $amount,"Withdraw From Account", ChangeTraderBalanceType::WITHDRAW);

            $amount = $request->input('amount');
            $paymentWallet = PaymentAccount::where('user_id', Auth::id())
                ->where('account_no', $request->wallet_address)
                ->first();

            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'category' => 'trading_account',
                'transaction_type' => 'withdrawal',
                'from_meta_login' => $tradingAccount->meta_login,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'payment_account_id' => $paymentWallet->id,
                'to_wallet_address' => $paymentWallet->account_no,
                'ticket' => $trade->getTicket(),
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'processing',
            ]);

         } catch (\Throwable $e) {
             if ($e->getMessage() == "Not found") {
                 TradingUser::firstWhere('meta_login', $tradingAccount->meta_login)->update(['acc_status' => 'inactive']);
             } else {
                 Log::error($e->getMessage());
             }
             return back()
                 ->with('toast', [
                     'title' => 'Trading account error',
                     'type' => 'error'
                 ]);
         }


        // return back()->with('toast', [
        //     'title' => trans('public.toast_withdrawal_success'),
        //     'type' => 'success',
        // ]);
        // disable trade

        // Set notification data in the session
        return back()->with('notification', [
            'details' => $transaction,
            'type' => 'withdrawal',
            // 'withdrawal_type' => 'rebate' this not put show meta_login put rebate show Rebate put bonus show Bonus
        ]);
    }

    public function internal_transfer(Request $request)
    {
         $request->validate([
             'account_id' => 'required|exists:trading_accounts,id',
             'amount' => ['required', 'numeric', 'gte:50'],
         ]);

         $conn = (new CTraderService)->connectionStatus();
         if ($conn['code'] != 0) {
             return back()
                 ->with('toast', [
                     'title' => 'Connection Error',
                     'type' => 'error'
                 ]);
         }

         $tradingAccount = TradingAccount::find($request->account_id);
         (new CTraderService)->getUserInfo(collect($tradingAccount));

         $tradingAccount = TradingAccount::find($request->account_id);
         $amount = $request->input('amount');
         $to_meta_login = $request->input('to_meta_login');

         if (($tradingAccount->balance - $tradingAccount->credit) < $amount) {
             throw ValidationException::withMessages(['wallet' => trans('public.insufficient_balance')]);
         }

         try {
            if ($tradingAccount->accountType->category == 'promotion' && $tradingAccount->credit > 0) {
                $credit_amount = $tradingAccount->credit;
                $tradeCredit = (new CTraderService)->createTrade($tradingAccount->meta_login, $credit_amount, "Credit Withdraw From Account", ChangeTraderBalanceType::WITHDRAW_NONWITHDRAWABLE_BONUS);
                $ticketCredit = $tradeCredit->getTicket();
                Transaction::create([
                    'user_id' => Auth::id(),
                    'category' => 'trading_account',
                    'transaction_type' => 'credit_withdrawal',
                    'from_meta_login' => $tradingAccount->meta_login,
                    'ticket' => $ticketCredit,
                    'transaction_number' => RunningNumberService::getID('transaction'),
                    'amount' => $credit_amount,
                    'transaction_charges' => 0,
                    'transaction_amount' => $credit_amount,
                    'status' => 'successful',
                    'comment' => 'Credit Withdrawal'
                ]);
            }

            $tradeFrom = (new CTraderService)->createTrade($tradingAccount->meta_login, $amount, "Withdraw From Account", ChangeTraderBalanceType::WITHDRAW);
            $tradeTo = (new CTraderService)->createTrade($to_meta_login, $amount, "Deposit To Account", ChangeTraderBalanceType::DEPOSIT);

            $ticketFrom = $tradeFrom->getTicket();
            $ticketTo = $tradeTo->getTicket();
            Transaction::create([
                'user_id' => Auth::id(),
                'category' => 'trading_account',
                'transaction_type' => 'account_to_account',
                'from_meta_login' => $tradingAccount->meta_login,
                'to_meta_login' => $to_meta_login,
                'ticket' => $ticketFrom . ','. $ticketTo,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'successful',
                'comment' => 'to ' . $to_meta_login
            ]);
         } catch (\Throwable $e) {
             if ($e->getMessage() == "Not found") {
                 TradingUser::firstWhere('meta_login', $tradingAccount->meta_login)->update(['acc_status' => 'inactive']);
             } else {
                 Log::error($e->getMessage());
             }
             return response()->json(['success' => false, 'message' => $e->getMessage()]);
         }

        return back()->with('toast', [
            'title' => trans('public.toast_internal_transfer_success'),
            'type' => 'success',
        ]);
    }

    public function change_leverage(Request $request)
    {
        $request->validate([
            'account_id' => 'required',
        ]);

        $conn = (new CTraderService)->connectionStatus();
         if ($conn['code'] != 0) {
             return back()
                 ->with('toast', [
                     'title' => 'Connection Error',
                     'type' => 'error'
                 ]);
         }

        $account = TradingAccount::find($request->account_id);
        // Log::debug($account);
        try {
            (new CTraderService)->updateLeverage($account->meta_login, $request->leverage);
        } catch (\Throwable $e) {
            if ($e->getMessage() == "Not found") {
                TradingUser::firstWhere('meta_login', $account->meta_login)->update(['acc_status' => 'inactive']);
            } else {
                Log::error($e->getMessage());
            }
            return back()
                ->with('toast', [
                    'title' => 'Update leverage error',
                    'type' => 'error'
                ]);
        }

        return back()->with('toast', [
            'title' => trans('public.toast_change_leverage_success'),
            'type' => 'success',
        ]);

    }

    public function missing_amount(Request $request)
    {
        Validator::make($request->all(), [
            'meta_login' => ['required', 'exists:trading_accounts,meta_login'],
            'amount' => ['required'],
            'deposit_date' => 'required',
            'txid' => 'required',
            'screenshot' => 'required',
        ])->setAttributeNames([
            'meta_login' => trans('public.account'),
            'amount' => trans('public.amount'),
            'deposit_date' => trans('public.deposit_date'),
            'txid' => trans('public.txid'),
            'screenshot' => trans('public.screenshot'),
        ])->validate();

        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'category' => 'trading_account',
            'transaction_type' => 'deposit',
            'to_meta_login' => $request->meta_login,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'txn_hash' => $request->txid,
            'amount' => $request->amount,
            'transaction_charges' => 0,
            'transaction_amount' => $request->amount,
            'status' => 'processing',
            'comment' => $request->deposit_date . '|' . 'Missing Amount'
        ]);

        if ($request->hasfile('screenshot')) {
            $transaction->addMedia($request->screenshot)->toMediaCollection('payment_receipt');
        }

        Notification::route('mail', 'payment@currenttech.pro')
            ->notify(new DepositApprovalNotification($transaction));

        return back()->with('toast', [
            'title' => trans('public.toast_missing_amount_success'),
            'type' => 'success',
        ]);

    }

    public function delete_account(Request $request)
    {
        $request->validate([
            'account_id' => ['required', 'exists:trading_accounts,id'],
            'type' => ['nullable', 'string']
        ]);

        $account = TradingAccount::find($request->account_id);
        $trading_user = TradingUser::where('meta_login', $account->meta_login)
            ->first();

        if ($account->balance == 0) {
            try {
                (new CTraderService)->deleteTrader($account->meta_login);

                $account->delete();
                $trading_user->delete();
            } catch (\Throwable $e) {
                Log::error($e->getMessage());

                return back()->with('toast', [
                    'title' => 'CTrader connection error',
                    'type' => 'error',
                ]);
            }
        }
        else{
            return back()->with('toast', [
                'title' => trans('public.toast_delete_account_error'),
                'type' => 'error',
            ]);
        }

        $successTitle = trans('public.toast_delete_account_success');
        if ($request->type === 'demo') {
            $successTitle = trans('public.toast_delete_demo_account_success');
        }

        return back()->with('toast', [
            'title' => $successTitle,
            'type' => 'success',
        ]);
    }

    //payment gateway return function
    public function depositReturn(Request $request)
    {
        return to_route('dashboard');
    }

    public function depositCallback(Request $request)
    {
        $data = $request->all();

        $result = [
            "token" => $data['vCode'],
            "from_wallet_address" => $data['from_wallet'],
            "to_wallet_address" => $data['to_wallet'],
            "txn_hash" => $data['txID'],
            "transaction_number" => $data['transaction_number'],
            "amount" => $data['transfer_amount'],
            "transfer_amount_type" => $data['transfer_amount_type'] ?? null,
            "status" => $data["status"],
            "remarks" => 'System Approval',
        ];

        $transaction = Transaction::query()
            ->where('transaction_number', $result['transaction_number'])
            ->first();

        $payoutSetting = config('payment-gateway');
        $domain = $_SERVER['HTTP_HOST'];

        if ($domain === 'login.qcgexchange.com') {
            $selectedPayout = $payoutSetting['live'];
        } else {
            $selectedPayout = $payoutSetting['staging'];
        }

        $dataToHash = md5($transaction->transaction_number . $selectedPayout['appId'] . $selectedPayout['merchantId']);
        $status = $result['status'] == 'success' ? 'successful' : 'failed';

        if ($result['token'] === $dataToHash) {
            $transaction->update([
                'from_wallet_address' => $result['from_wallet_address'],
                'to_wallet_address' => $result['to_wallet_address'],
                'txn_hash' => $result['txn_hash'],
                'transaction_charges' => 0,
                'status' => $status,
                'remarks' => $result['remarks'],
                'approved_at' => now()
            ]);

            $original_amount = $result['amount'];
            $formatted_amount = floor($original_amount * 100) / 100;

            if ($result['transfer_amount_type'] == 'invalid') {
                $now = Carbon::now('Asia/Kuala_Lumpur');
                $currentDay = $now->dayOfWeekIso;

                $setting = SettingAutoApproval::where('day', $currentDay)
                ->where('type', 'deposit')
                ->where('status', 'active')
                ->first();

                if ($setting) {
                    $startTime = Carbon::createFromFormat('H:i', $setting->start_time, 'Asia/Kuala_Lumpur')
                        ->setDateFrom($now);

                    $endTime = Carbon::createFromFormat('H:i', $setting->end_time, 'Asia/Kuala_Lumpur')
                        ->setDateFrom($now);

                    $lowerBound = $transaction->amount - $setting->spread_amount;
                    $upperBound = $transaction->amount + $setting->spread_amount;
                    
                    if (
                        $now->between($startTime, $endTime) &&
                        $formatted_amount >= $lowerBound &&
                        $formatted_amount <= $upperBound
                    ) {
                        $transaction->update([
                            'transaction_amount' => $formatted_amount,
                            'status' => 'successful',
                            'remarks' => $result['remarks'],
                            'approved_at' => now()
                        ]);
                    } else {
                    $transaction->update([
                        'transaction_amount' => $formatted_amount,
                        'status' => 'processing',
                    ]);

                    Notification::route('mail', 'payment@currenttech.pro')
                        ->notify(new DepositApprovalNotification($transaction));
                    }
                }

            } else {
                $transaction->update([
                    'amount' => $formatted_amount,
                    'transaction_amount' => $formatted_amount,
                    'status' => $status,
                    'remarks' => $result['remarks'],
                    'approved_at' => now()
                ]);
            }

            if ($transaction->status == 'successful' && $transaction->transaction_type == 'deposit') {
                try {
                    $transactionAmount = round($transaction->transaction_amount, 2);

                    $trade = (new CTraderService)->createTrade(
                        $transaction->to_meta_login,
                        $transactionAmount,
                        "Deposit balance",
                        'DEPOSIT'
                    );
                } catch (\Throwable $e) {
                    if ($e->getMessage() == "Not found") {
                        TradingUser::firstWhere('meta_login', $transaction->to)->update(['acc_status' => 'inactive']);
                    } else {
                        Log::error($e->getMessage());
                    }
                    return response()->json(['success' => false, 'message' => $e->getMessage()]);
                }
                $ticket = $trade->getTicket();
                $transaction->ticket = $ticket;
                $transaction->save();

                $tradingAccount = TradingAccount::where('meta_login', $transaction->to_meta_login)->first();

                if ($tradingAccount->promotion_type == 'deposit') {
                    $claimable_status = false;
                    $bonus_amount = 0;
                    $achievedAmount = $tradingAccount->achieved_amount ?? 0;
                    $targetAmount = ($tradingAccount->bonus_amount_type === 'specified_amount')
                                ? $tradingAccount->min_threshold
                                : $tradingAccount->target_amount;

                    Log::info('Promotion detected');
                    if (!($tradingAccount->is_claimed === 'expired' || $tradingAccount->is_claimed === 'completed' || ($targetAmount !== null && $achievedAmount >= $targetAmount))
                        && $tradingAccount->promotion_type == 'deposit' && ($tradingAccount->applicable_deposit !== 'first_deposit_only' || $achievedAmount == 0)) {
                        if ($tradingAccount->bonus_amount_type === 'percentage_of_deposit') {
                            $bonus_amount = ($transaction->amount * $tradingAccount->bonus_amount / 100);

                            if ($transaction->amount >= $tradingAccount->min_threshold ||
                            ($tradingAccount->achieved_amount * 100 /  $tradingAccount->bonus_amount) >= $tradingAccount->min_threshold) {
                                // achievedAmount = 600 target_amount = 1000 bonus_amount = 600 , remaining should be 400
                                $remainingAmount = $tradingAccount->target_amount - $achievedAmount;
                                if ($bonus_amount > $remainingAmount) {
                                    $bonus_amount = $remainingAmount;
                                }
                                $tradingAccount->claimable_amount = $tradingAccount->claimable_amount + $bonus_amount;
                                $tradingAccount->achieved_amount = $achievedAmount + $bonus_amount;
                                if ($tradingAccount->is_claimed !== 'pending') {
                                    $tradingAccount->is_claimed = 'claimable';
                                }
                            }
                        }
                        else{
                            $bonus_amount = $transaction->amount;
                            $remainingAmount = $tradingAccount->min_threshold - $achievedAmount;

                            if ($achievedAmount + $bonus_amount >= $tradingAccount->min_threshold) {
                                $bonus_amount = $remainingAmount;
                                $tradingAccount->claimable_amount = $tradingAccount->bonus_amount;
                                if ($tradingAccount->is_claimed !== 'pending') {
                                    $tradingAccount->is_claimed = 'claimable';
                                }
                            }
                            $tradingAccount->achieved_amount = $achievedAmount + $bonus_amount;
                        }
                        Log::info('Updated Account : ' . $tradingAccount->meta_login);
                        $tradingAccount->save();
                    }
                }

                Notification::route('mail', 'payment@currenttech.pro')
                    ->notify(new DepositApprovalNotification($transaction));

                return response()->json(['success' => true, 'message' => 'Deposit Success']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Deposit Failed']);
    }
}
