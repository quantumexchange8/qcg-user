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
use App\Services\CTraderService;
use App\Services\RunningNumberService;
use App\Services\DropdownOptionService;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function index()
    {
        $account_types = AccountType::select('id', 'name')
                        ->where('show_register', 1)
                        ->get();
        $leverages = SettingLeverage::select('leverage', 'value')->get();

        return Inertia::render('Accounts/Account', [
            // 'account_types' => $account_types,
            // 'leverages' => $leverages,
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

    public function create_live_account(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'accountType' => 'required|exists:account_types,account_group',
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

        // Retrieve the account type by account_group
        $accountType = AccountType::where('account_group', $request->accountType)->first();

        // Check the number of existing trading accounts for this user and account type
        $existingAccountsCount = TradingAccount::where('user_id', $user->id)
            ->where('account_type_id', $accountType->id)
            ->count();

        // Check if the user has reached the maximum number of accounts
        if ($existingAccountsCount >= $accountType->maximum_account_number) {
            return back()->with('toast', [
                'title' => trans("public.account_limit_reach"),
                'message' => trans("public.account_limit_reach_message"),
                'type' => 'warning',
            ]);
        }

        if (App::environment('production')) {
            $mainPassword = Str::random(8);
            $investorPassword = Str::random(8);
            (new CTraderService)->createUser($user,  $mainPassword, $investorPassword, $accountType->account_group, $request->leverage, $accountType->id, null, null, '');
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
            ->whereHas('account_type', function($q) use ($accountType) {
                $q->where('category', $accountType);
            })
            ->get();

        try {
            foreach ($trading_accounts as $trading_account) {
                (new CTraderService)->getUserInfo($trading_account->meta_login);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

        $liveAccounts = TradingAccount::with('account_type')
            ->where('user_id', $user->id)
            ->when($accountType, function ($query) use ($accountType) {
                return $query->whereHas('accountType', function ($query) use ($accountType) {
                    $query->where('category', $accountType);
                });
            })
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'user_id' => $account->user_id,
                    'meta_login' => $account->meta_login,
                    'balance' => $account->balance,
                    'credit' => $account->credit,
                    'leverage' => $account->margin_leverage,
                    'equity' => $account->equity,
                    'account_type' => $account->accountType->slug,
                    'account_type_leverage' => $account->accountType->leverage,
                    'account_type_color' => $account->accountType->color,
                    'is_active' => $account->status,
                ];
            });

        return response()->json($liveAccounts);
    }

    public function getAccountReport(Request $request)
    {
        $meta_login = $request->query('meta_login');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $type = $request->query('type');

        $query = Transaction::query()->where('status', 'successful');

        if ($meta_login) {
            $query->where(function($subQuery) use ($meta_login) {
                $subQuery->where('from_meta_login', $meta_login)
                    ->orWhere('to_meta_login', $meta_login);
            });
        }

        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
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
        $request->validate([
            'meta_login' => ['required', 'exists:trading_accounts,meta_login'],
            'checkbox1' => 'accepted',
            'checkbox2' => 'accepted', 
        ]);

        $user = Auth::user();

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'category' => 'trading_account',
            'transaction_type' => 'deposit',
            'to_meta_login' => $request->meta_login,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'status' => 'processing',
        ]);

        $token = Str::random(40);

        $payoutSetting = config('payment-gateway');
        $domain = $_SERVER['HTTP_HOST'];

        if ($domain === 'qcgexchange.com') {
            $selectedPayout = $payoutSetting['live'];
        } else {
            $selectedPayout = $payoutSetting['staging'];
        }

        $vCode = md5($selectedPayout['appId'] . $transaction->transaction_number . $selectedPayout['merchantId'] . $selectedPayout['ttKey']);

        $params = [
            'userName' => $user->first_name,
            'userEmail' => $user->email,
            'orderNumber' => $transaction->transaction_number,
            'userId' => $user->id,
            'merchantId' => $selectedPayout['merchantId'],
            'vCode' => $vCode,
            'token' => $token,
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
        //         TradingUser::firstWhere('meta_login', $tradingAccount->meta_login)->update(['acc_status' => 'Inactive']);
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

    public function withdrawal_from_account(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => ['required', 'exists:trading_accounts,id'],
            'amount' => ['required', 'numeric', 'gte:1'],
            'wallet_address' => ['required']
        ])->setAttributeNames([
            'account_id' => trans('public.account'),
            'amount' => trans('public.amount'),
            'wallet_address' => trans('public.receiving_wallet'),
        ]);
        $validator->validate();

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

         if ($tradingAccount->balance < $amount) {
             throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
         }

         try {
             $trade = (new CTraderService)->createTrade($tradingAccount->meta_login, $amount,"Withdraw From Account", ChangeTraderBalanceType::WITHDRAW);
         } catch (\Throwable $e) {
             if ($e->getMessage() == "Not found") {
                 TradingUser::firstWhere('meta_login', $tradingAccount->meta_login)->update(['acc_status' => 'Inactive']);
             } else {
                 Log::error($e->getMessage());
             }
             return back()
                 ->with('toast', [
                     'title' => 'Trading account error',
                     'type' => 'error'
                 ]);
         }

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

        // disable trade

        // Set notification data in the session
        return redirect()->back()->with('notification', [
            'details' => $transaction,
            'type' => 'withdrawal',
            // 'withdrawal_type' => 'rebate' this not put show meta_login put rebate show Rebate put bonus show Bonus
        ]);
    }

    public function internal_transfer(Request $request)
    {
         $request->validate([
             'account_id' => 'required|exists:trading_accounts,id',
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

         if ($tradingAccount->balance < $amount) {
             throw ValidationException::withMessages(['wallet' => trans('public.insufficient_balance')]);
         }

         try {
             $tradeFrom = (new CTraderService)->createTrade($tradingAccount->meta_login, $amount, "Withdraw From Account", ChangeTraderBalanceType::WITHDRAW);
             $tradeTo = (new CTraderService)->createTrade($to_meta_login, $amount, "Deposit To Account", ChangeTraderBalanceType::DEPOSIT);
         } catch (\Throwable $e) {
             if ($e->getMessage() == "Not found") {
                 TradingUser::firstWhere('meta_login', $tradingAccount->meta_login)->update(['acc_status' => 'Inactive']);
             } else {
                 Log::error($e->getMessage());
             }
             return response()->json(['success' => false, 'message' => $e->getMessage()]);
         }

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
                TradingUser::firstWhere('meta_login', $account->meta_login)->update(['acc_status' => 'Inactive']);
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
        $data = $request->all();

        Log::debug('deposit return ', $data);

        if ($data['response_status'] == 'success') {

            $result = [
                "amount" => $data['transfer_amount'],
                "transaction_number" => $data['transaction_number'],
                "txn_hash" => $data['txID'],
            ];

            $transaction = Transaction::query()
                ->where('transaction_number', $result['transaction_number'])
                ->first();

            $result['date'] = $transaction->approved_at;

            return redirect()->route('dashboard')->with('notification', [
                'details' => $transaction,
                'type' => 'deposit',
            ]);
        } else {
            return to_route('dashboard');
        }
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
            "status" => $data["status"],
            "remarks" => 'System Approval',
        ];

        $transaction = Transaction::query()
            ->where('transaction_number', $result['transaction_number'])
            ->first();

        $payoutSetting = config('payment-gateway');
        $domain = $_SERVER['HTTP_HOST'];

        if ($domain === 'qcgexchange.com') {
            $selectedPayout = $payoutSetting['live'];
        } else {
            $selectedPayout = $payoutSetting['staging'];
        }

        $dataToHash = md5($transaction->transaction_number . $selectedPayout['appId'] . $selectedPayout['merchantId']);
        $status = $result['status'] == 'success' ? 'successful' : 'failed';

        if ($result['token'] === $dataToHash) {
            //proceed approval
            $transaction->update([
                'from_wallet_address' => $result['from_wallet_address'],
                'to_wallet_address' => $result['to_wallet_address'],
                'txn_hash' => $result['txn_hash'],
                'amount' => $result['amount'],
                'transaction_charges' => 0,
                'transaction_amount' => $result['amount'],
                'status' => $status,
                'remarks' => $result['remarks'],
                'approved_at' => now()
            ]);

//            Notification::route('mail', 'payment@currenttech.pro')
//                ->notify(new DepositApprovalNotification($payment));

            if ($transaction->status == 'successful') {
                if ($transaction->transaction_type == 'deposit') {
                    try {
                        $trade = (new CTraderService)->createTrade($transaction->to_meta_login, $transaction->transaction_amount, "Deposit balance", ChangeTraderBalanceType::DEPOSIT);
                    } catch (\Throwable $e) {
                        if ($e->getMessage() == "Not found") {
                            TradingUser::firstWhere('meta_login', $transaction->to)->update(['acc_status' => 'Inactive']);
                        } else {
                            Log::error($e->getMessage());
                        }
                        return response()->json(['success' => false, 'message' => $e->getMessage()]);
                    }
                    $ticket = $trade->getTicket();
                    $transaction->ticket = $ticket;
                    $transaction->save();

                    Notification::route('mail', 'payment@currenttech.pro')
                        ->notify(new DepositApprovalNotification($transaction));

                    return response()->json(['success' => true, 'message' => 'Deposit Success']);

                }
            }
        }

        return response()->json(['success' => false, 'message' => 'Deposit Failed']);
    }
    
    // public function createTradingAccount(Request $request)
    // {
    //     $user = Auth::user();

    //     $cTraderService = new CTraderService;

    //     $conn = $cTraderService->connectionStatus();
    //     if ($conn['code'] != 0) {
    //         return back()
    //             ->with('toast', [
    //                 'title' => 'Connection Error',
    //                 'type' => 'error'
    //             ]);
    //     }

    //     /*
    //     $mainPassword = Str::random(8);
    //     $investorPassword = Str::random(8);
    //      Mail::to($user->email)->send(new NewMetaAccount($mainPassword, $investorPassword));
    //     return true; */

    //     $account_type = $request->account_type;
    //     $mainPassword = Str::random(8);
    //     $investorPassword = Str::random(8);
    //     // $group = 'real\ETIQECNElite_A';
    //     // $group = Group::where('value', $group)->first()->value('meta_group_name');
    //     $account_type = AccountType::with('metaGroup')->where('id', $account_type)->get()->value('metaGroup.meta_group_name');

    //     // $remarks = 'TW Test Trading Group';
    //     $remarks = $user->remark;
    //     $ctAccount = (new CTraderService)->createUser($user,  $mainPassword, $investorPassword, $account_type, $request->leverage, $request->account_type, null, null, $remarks);
    //     //Mail::to($user->email)->send(new NewMetaAccount($ctAccount['login'], $mainPassword, $investorPassword));
    //     return back()->with('toast', 'Successfully Created Trading Account');
    //     // return true;
    // }

    // public function getTradingAccounts(Request $request)
    // {
    //     $user = Auth::user();
    //     // $cTraderService = new CTraderService;

    //     // $conn = $cTraderService->connectionStatus();
    //     // if ($conn['code'] != 0) {
    //     //     return back()
    //     //         ->with('toast', [
    //     //             'title' => 'Connection Error',
    //     //             'type' => 'error'
    //     //         ]);
    //     // }

    //     // try {
    //     //     $cTraderService->getUserInfo($request->meta_login);
    //     // } catch (\Throwable $e) {
    //     //     Log::error($e->getMessage());

    //     //     return back()
    //     //         ->with('toast', [
    //     //             'title' => 'No Account Found',
    //     //             'type' => 'error'
    //     //         ]);
    //     // }

    //     // Fetch trading accounts based on user ID
    //     $tradingAccounts = TradingAccount::query()
    //         ->where('user_id', $user->id)
    //         ->get() // Fetch the results from the database
    //         ->map(function($trading_account) {
    //             return [
    //                 'id' => $trading_account->id,
    //                 'meta_login' => $trading_account->meta_login,
    //                 'account_type' => $trading_account->accountType->name,
    //                 'balance' => $trading_account->balance,
    //                 'credit' => $trading_account->credit,
    //                 'equity' => $trading_account->equity,
    //                 'leverage' => $trading_account->margin_leverage,
    //                 // 'account_type_color' => $trading_account->accountType->color,
    //                 'updated_at' => $trading_account->updated_at,
    //             ];
    //         });

    //     // Return the response as JSON
    //     return response()->json([
    //         'tradingAccounts' => $tradingAccounts,
    //     ]);
    // }

}
