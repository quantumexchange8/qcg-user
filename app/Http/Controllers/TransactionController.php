<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AccountType;
use App\Models\Transaction;

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
            ->get()
            ->map(function ($transaction) {
                $description = $transaction->transaction_type;
                $asset = $transaction->to_meta_login ? $transaction->to_meta_login : 'Unknown';

                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'transaction_number' => $transaction->transaction_number,
                    'description' => $description,
                    'asset' => $asset,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'to_wallet_address' => $transaction->to_wallet_address,
                    'from_wallet_address' => $transaction->from_wallet_address,
                    'txn_hash' => $transaction->txn_hash,
                    'remarks' => $transaction->comment,
                ];
            });

        return response()->json([
            'transactions' => $transactions,
            // 'totalDeposit' => $bonusQuery->sum('amount'),
            // 'totalWithdrawal' => $bonusQuery->sum('amount'),
        ]);  
    }
}
