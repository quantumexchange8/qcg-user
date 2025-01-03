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
            'totalDeposit' => $totalDeposit,
            'totalWithdrawal' => $totalWithdrawal,
        ]);
    }

    public function getTransactionHistory(Request $request)
    {
        $id = Auth::id();

        $query = Transaction::where('user_id', $id);

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

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
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $description = $request->query('description');

        $query = Transaction::where(['category' => 'rebate_wallet', 'user_id' => Auth::id()])->where('status', 'successful');

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

        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
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

        if ($transaction->status != 'processing') {
            return redirect()->back()->with('toast', 'It appears you have already completed approval action');
        }

        $status = $action == "approve" ? "successful" : "failed";
        $transaction->status = $status;
        $transaction->remarks = 'System Approval';
        $transaction->approved_at = now();
        $transaction->save();

        if ($transaction->status == "successful") {
            try {
                $trade = (new CTraderService)->createTrade($transaction->to_meta_login, $transaction->transaction_amount, "Deposit balance", 'DEPOSIT');
                $transaction->ticket = $trade->getTicket();
                $transaction->save();

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
