<?php

namespace App\Http\Controllers;

use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PaymentAccount;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\AccountType;
use App\Services\CTraderService;
use App\Services\RunningNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index()
    {
        $id = Auth::id();

        $totalDeposit = Transaction::where('user_id', $id)
            ->where('transaction_type', 'deposit')
            ->where('status', 'successful')
            ->sum('amount');

        $totalWithdrawal = Transaction::where('user_id', $id)
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'successful')
            ->sum('amount');

        return Inertia::render('Transaction/Transaction', [
            'totalDeposit' => (float) $totalDeposit,
            'totalWithdrawal' => (float) $totalWithdrawal,
        ]);
    }

    public function getTransactionHistory(Request $request)
    {
        $id = Auth::id();
        $query = Transaction::where('user_id', $id);

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

        $query->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->where('transaction_type', $type);
        }
        else {
            $query->where(function ($query) {
                $query->where('transaction_type', 'deposit')
                      ->orWhere('transaction_type', 'withdrawal');
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
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
                    'amount' => $transaction->amount ?? 0,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'status' => $transaction->status,
                    'comment' => $transaction->comment,
                    'remarks' => $transaction->remarks,
                    'created_at' => $transaction->created_at,
                    'wallet_name' => $transaction->payment_account->payment_account_name ?? '-',
                    'wallet_address' => $transaction->payment_account->account_no ?? '-',
                ];
            });

        return response()->json([
            'transactions' => $transactions,
            // 'totalDeposit' => $bonusQuery->sum('amount'),
            // 'totalWithdrawal' => $bonusQuery->sum('amount'),
        ]);
    }

    public function walletTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => ['required', 'exists:wallets,id'],
            'meta_login' => ['required']
        ])->setAttributeNames([
            'wallet_id' => trans('public.wallet'),
            'meta_login' => trans('public.transfer_to'),
        ]);
        $validator->validate();

        $amount = $request->amount;
        $wallet = Wallet::find($request->wallet_id);

        $conn = (new CTraderService)->connectionStatus();
        if ($conn['code'] != 0) {
            return back()
                ->with('toast', [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]);
        }

        $tradingAccount = TradingAccount::where('meta_login', $request->meta_login)->first();
        (new CTraderService)->getUserInfo($tradingAccount->meta_login);

        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        try {
            $trade = (new CTraderService)->createTrade($tradingAccount->meta_login, $amount, "Rebate to account", ChangeTraderBalanceType::DEPOSIT);
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

        Transaction::create([
            'user_id' => Auth::id(),
            'category' => 'rebate_wallet',
            'transaction_type' => 'transfer_to_account',
            'from_wallet_id' => $wallet->id,
            'to_meta_login' => $tradingAccount->meta_login,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'ticket' => $trade->getTicket(),
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'successful',
            'old_wallet_amount' => $wallet->balance,
            'new_wallet_amount' => $wallet->balance -= $amount,
        ]);

        $wallet->save();

        return back()->with('toast', [
            'title' => trans("public.toast_transfer_success"),
            'type' => 'success',
        ]);
    }

    public function walletWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => ['required', 'exists:wallets,id'],
            'amount' => ['required', 'numeric', 'gte:50'],
            'wallet_address' => ['required']
        ])->setAttributeNames([
            'wallet_id' => trans('public.wallet'),
            'amount' => trans('public.amount'),
            'wallet_address' => trans('public.receiving_wallet'),
        ]);
        $validator->validate();

        $user = Auth::user();
        $amount = $request->amount;
        $wallet = Wallet::find($request->wallet_id);
        $paymentWallet = PaymentAccount::where('user_id', Auth::id())
            ->where('account_no', $request->wallet_address)
            ->first();

        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'category' => $wallet->type,
            'transaction_type' => 'withdrawal',
            'from_wallet_id' => $wallet->id,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'payment_account_id' => $paymentWallet->id,
            'to_wallet_address' => $paymentWallet->account_no,
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'old_wallet_amount' => $wallet->balance,
            'new_wallet_amount' => $wallet->balance -= $amount,
            'status' => 'processing',
        ]);

        $wallet->save();

        return redirect()->back()->with('notification', [
            'details' => $transaction,
            'type' => 'withdrawal',
            'withdrawal_type' => $transaction->category == 'rebate_wallet' ? 'rebate' : 'bonus'
        ]);
    }

    public function applyRebate()
    {
        $user = Auth::user();

        if ($user->rebate_amount > 0) {
            $rebate_wallet = $user->rebate_wallet;

            Transaction::create([
                'user_id' => $user->id,
                'category' => 'rebate_wallet',
                'transaction_type' => 'apply_rebate',
                'to_wallet_id' => $rebate_wallet->id,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'amount' => $user->rebate_amount,
                'transaction_charges' => 0,
                'transaction_amount' => $user->rebate_amount,
                'old_wallet_amount' => $rebate_wallet->balance,
                'new_wallet_amount' => $rebate_wallet->balance += $user->rebate_amount,
                'status' => 'successful',
                'approved_at' => now(),
            ]);

            $rebate_wallet->save();

            $user->rebate_amount = 0;
            $user->save();

            return back()->with('toast', [
                'title' => trans("public.toast_apply_rebate_success"),
                'type' => 'success',
            ]);
        } else {
            return back()->with('toast', [
                'title' => trans("public.unable_to_apply_rebate"),
                'message' => trans("public.toast_apply_rebate_error"),
                'type' => 'error',
            ]);
        }
    }

    public function getRebateTransactions(Request $request)
    {
        $description = $request->query('description');
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

        $query = Transaction::where(['category' => 'rebate_wallet', 'user_id' => Auth::id()])
                ->where('status', 'successful')
                ->whereBetween('created_at', [$startDate, $endDate]);

        if ($description && $description !== 'all') {
            if ($description == 'rebate_payout'){
                $query->where('transaction_type', 'rebate_payout');
            }
            elseif ($description == 'apply_rebate'){
                $query->where('transaction_type', 'apply_rebate');
            }
            elseif ($description == 'transfer'){
                $query->where('transaction_type', 'transfer_to_account');
            }
            elseif ($description == 'withdrawal'){
                $query->where('transaction_type', 'withdrawal');
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
                    'wallet_name' => $transaction->payment_account->payment_account_name ?? '-'
                ];
            });

        return response()->json([
            'transactions' => $transactions,
        ]);
    }

    public function depositApproval(Request $request)
    {
        $transaction = Transaction::find($request->transaction_id);
        $action = $request->action;

        if ($action == 'reject') {
            Validator::make($request->all(), [
                'remarks' => ['required'],
            ])->setAttributeNames([
                'remarks' => trans('public.remarks'),
            ])->validate();
        } else {
            Validator::make($request->all(), [
                'amount' => ['required'],
                'txn_hash' => ['required'],
            ])->setAttributeNames([
                'amount' => trans('public.amount'),
                'txn_hash' => trans('public.txid'),
            ])->validate();
        }

        if ($transaction->status != 'processing') {
            return redirect()->back()->with('toast', 'It appears you have already completed approval action');
        }

        $status = $action == "approve" ? "successful" : "failed";
        $transaction->amount = $request->amount;
        $transaction->transaction_amount = $request->transaction_amount;
        $transaction->txn_hash = $request->txn_hash;
        $transaction->status = $status;
        $transaction->remarks = $action == 'reject' ? $request->remarks : 'System Approval';
        $transaction->approved_at = now();
        $transaction->save();

        if ($transaction->status == "successful") {
            try {
                $trade = (new CTraderService)->createTrade($transaction->to_meta_login, $transaction->transaction_amount, "Deposit balance", 'DEPOSIT');
                $transaction->ticket = $trade->getTicket();
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
                            $bonus_amount = ($transaction->transaction_amount * $tradingAccount->bonus_amount / 100);

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
                            $bonus_amount = $transaction->transaction_amount;
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


                return back()->with('toast', [
                    'title' => 'Successfully Processed Deposit Request',
                    'type' => 'success',
                ]);
            } catch (\Throwable $e) {
                if ($e->getMessage() == "Not found") {
                    TradingUser::firstWhere('meta_login', $transaction->to_meta_login)->update(['acc_status' => 'inactive']);
                }
                Log::error($e->getMessage() . " " . $transaction->transaction_number);
            }
        }

        return redirect()->back()->with('toast', 'Successfully Rejected Deposit Request');
    }
}
