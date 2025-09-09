<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\RebateAllocation;
use App\Models\TradeRebateSummary;
use App\Models\SymbolGroup;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Team;
use App\Models\TeamHasUser;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Report/Report');
    }

    public function getRebateBreakdown(Request $request)
    {
        $userId = Auth::id();

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        // $monthYear = $request->input('selectedMonth');

        // if ($monthYear === 'select_all') {
        //     $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
        //     $endDate = Carbon::now()->endOfDay();
        // } elseif (str_starts_with($monthYear, 'last_')) {
        //     preg_match('/last_(\d+)_week/', $monthYear, $matches);
        //     $weeks = $matches[1] ?? 1;

        //     $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
        //     $endDate = Carbon::now()->subWeek($weeks)->endOfWeek(); 
        // } else {
        //     $carbonDate = Carbon::parse($monthYear);

        //     $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
        //     $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        // }

        // // Initialize query for rebate summary with date filtering
        // $query = TradeRebateSummary::with('symbolGroup')
        //     ->where('upline_user_id', $userId)
        //     ->whereNotNull('execute_at')
        //     ->whereBetween('closed_time', [$startDate, $endDate]);

        $query = TradeRebateSummary::with('symbolGroup')
            ->where('upline_user_id', $userId)
            ->whereNotNull('execute_at');

        if ($startDate && $endDate) {
            // Both startDate and endDate are provided
            $start = Carbon::parse($startDate, config('app.timezone'))->startOfDay()->utc();
            $end = Carbon::parse($endDate, config('app.timezone'))->endOfDay()->utc();

            $query->whereBetween('closed_time', [$start, $end]);
        }

        // Fetch rebate summary data
        $rebateSummary = $query->get(['symbol_group_id', 'volume', 'rebate']);

        // Retrieve all symbol groups with non-null display values
        $symbolGroups = SymbolGroup::whereNotNull('display')->pluck('display', 'id');

        // Aggregate rebate data in PHP
        $rebateSummaryData = $rebateSummary->groupBy('symbol_group_id')->map(function ($items) {
            return [
                'volume' => $items->sum('volume'),
                'rebate' => $items->sum('rebate'),
            ];
        });

        // Initialize final summary and totals
        $finalSummary = [];
        $totalVolume = 0;
        $totalRebate = 0;

        // Iterate over all symbol groups
        foreach ($symbolGroups as $id => $display) {
            // Retrieve data or use default values
            $data = $rebateSummaryData->get($id, ['volume' => 0, 'rebate' => 0]);

            // Add to the final summary
            $finalSummary[] = [
                'symbol_group' => $display,
                'volume' => $data['volume'],
                'rebate' => $data['rebate'],
            ];

            // Accumulate totals
            $totalVolume += $data['volume'];
            $totalRebate += $data['rebate'];
        }

        // Return the response with rebate summary, total volume, and total rebate
        return response()->json([
            'rebateBreakdown' => $finalSummary,
            'totalVolume' => $totalVolume,
            'totalRebate' => $totalRebate,
        ]);
    }

    public function getRebateDetails(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // $monthYear = $request->query('selectedMonth');

        // if ($monthYear === 'select_all') {
        //     $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
        //     $endDate = Carbon::now()->endOfDay();
        // } elseif (str_starts_with($monthYear, 'last_')) {
        //     preg_match('/last_(\d+)_week/', $monthYear, $matches);
        //     $weeks = $matches[1] ?? 1;

        //     $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
        //     $endDate = Carbon::now()->subWeek($weeks)->endOfWeek(); 
        // } else {
        //     $carbonDate = Carbon::parse($monthYear);

        //     $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
        //     $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        // }

        // Fetch all symbol groups from the database, ordered by the primary key (id)
        $allSymbolGroups = SymbolGroup::pluck('display', 'id')->toArray();

        $query = TradeRebateSummary::with('user')
            ->where('upline_user_id', Auth::id())
            ->whereNotNull('execute_at');

        if ($startDate && $endDate) {
            // Both startDate and endDate are provided
            $start = Carbon::parse($startDate, config('app.timezone'))->startOfDay()->utc();
            $end = Carbon::parse($endDate, config('app.timezone'))->endOfDay()->utc();

            $query->whereBetween('closed_time', [$start, $end]);
        }

        // Fetch rebate listing data
        $data = $query->latest()
            ->get()
            ->map(function ($item) {
            return [
                'user_id' => $item->user_id,
                'name' => $item->user->first_name,
                'email' => $item->user->email,
                'meta_login' => $item->meta_login,
                'execute_at' => Carbon::parse($item->closed_time)->format('Y/m/d'),
                'symbol_group' => $item->symbol_group_id,
                'volume' => $item->volume,
                'net_rebate' => $item->net_rebate,
                'rebate' => $item->rebate,
            ];
        });

        // Group data by user_id and meta_login
        $rebateDetails = $data->groupBy(function ($item) {
            return $item['user_id'] . '-' . $item['meta_login'];
        })->map(function ($group) use ($allSymbolGroups) {
            $group = collect($group);

            // Calculate overall volume and rebate for the user
            $volume = $group->sum('volume');
            $rebate = $group->sum('rebate');

            // Create summary by execute_at
            $summary = $group->groupBy('execute_at')->map(function ($executeGroup) use ($allSymbolGroups) {
                $executeGroup = collect($executeGroup);

                // Calculate details for each symbol group
                $details = $executeGroup->groupBy('symbol_group')->map(function ($symbolGroupItems) use ($allSymbolGroups) {
                    $symbolGroupId = $symbolGroupItems->first()['symbol_group'];

                    return [
                        'id' => $symbolGroupId,
                        'name' => $allSymbolGroups[$symbolGroupId] ?? 'Unknown',
                        'volume' => $symbolGroupItems->sum('volume'),
                        'net_rebate' => $symbolGroupItems->first()['net_rebate'] ?? 0,
                        'rebate' => $symbolGroupItems->sum('rebate'),
                    ];
                })->values();

                // Add missing symbol groups with volume, net_rebate, and rebate as 0
                foreach ($allSymbolGroups as $symbolGroupId => $symbolGroupName) {
                    if (!$details->pluck('id')->contains($symbolGroupId)) {
                        $details->push([
                            'id' => $symbolGroupId,
                            'name' => $symbolGroupName,
                            'volume' => 0,
                            'net_rebate' => 0,
                            'rebate' => 0,
                        ]);
                    }
                }

                // Sort the symbol group details array to match the order of symbol groups
                $details = $details->sortBy('id')->values();

                return [
                    'execute_at' => $executeGroup->first()['execute_at'],
                    'volume' => $executeGroup->sum('volume'),
                    'rebate' => $executeGroup->sum('rebate'),
                    'details' => $details,
                ];
            })->values();

            // Return rebateDetails item with summaries included
            return [
                'user_id' => $group->first()['user_id'],
                'name' => $group->first()['name'],
                'email' => $group->first()['email'],
                'meta_login' => $group->first()['meta_login'],
                'volume' => $volume,
                'rebate' => $rebate,
                'summary' => $summary,
            ];
        })->values();

        // Return JSON response with combined rebateDetails and details
        return response()->json([
            'rebateDetails' => $rebateDetails
        ]);
    }

    public function getGroupTransaction(Request $request)
    {
        $user = Auth::user();
        // $groupIds = $user->getChildrenIds();
        // $groupIds[] = $user->id;

        $transactionType = $request->query('type');
        
        $monthYear = $request->input('selectedMonth');
        $group = $request->input('selectedGroup');
        $search = $request->input('search');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek(); 
        } else {
            $carbonDate = Carbon::parse($monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        if ($group === 'personal') {
            $groupIds = $user->directChildren()->pluck('id')->toArray();
            $groupIds[] = $user->id;
        } else {
            $team_id = TeamHasUser::where('user_id', $user->id)->value('team_id');
            $team_user_ids = TeamHasUser::where('team_id', $team_id)
            ->pluck('user_id')
            ->toArray();

            // Log::info($team_user_ids);
            $groupIds = $user->getChildrenIds();
            $groupIds[] = $user->id;
            // Log::info($groupIds);
            $groupIds = array_intersect($groupIds, $team_user_ids);
            // Log::info($groupIds);
        }

        if ($search) {
            $searchUserIds = User::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('chinese_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            })->pluck('id')->toArray();

            $groupIds = array_intersect($groupIds, $searchUserIds);
        }

        $transactionTypes = match($transactionType) {
            'deposit' => ['deposit', 'balance_in'],
            'withdrawal' => ['withdrawal', 'balance_out'],
            default => []
        };

        // Initialize the query for transactions
        $query = Transaction::whereIn('transaction_type', $transactionTypes)
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->whereBetween('approved_at', [$startDate, $endDate]);

        $transactions = $query->latest()
            ->get()
            ->map(function ($transaction) {
                $metaLogin = $transaction->to_meta_login ?: $transaction->from_meta_login;

                // Check for withdrawal type and modify meta_login based on category
                if ($transaction->transaction_type === 'withdrawal') {
                    switch ($transaction->category) {
                        case 'trading_account':
                            $metaLogin = $transaction->from_meta_login;
                            break;
                        case 'rebate_wallet':
                            $metaLogin = 'rebate';
                            break;
                        case 'bonus_wallet':
                            $metaLogin = 'bonus';
                            break;
                    }
                }

                // Return the formatted transaction data
                return [
                    'created_at' => $transaction->approved_at,
                    'user_id' => $transaction->user_id,
                    'name' => $transaction->user->first_name,
                    'email' => $transaction->user->email,
                    'meta_login' => $metaLogin,
                    'transaction_amount' => $transaction->transaction_amount,
                ];
            });

        // Calculate total deposit and withdrawal amounts for the given date range
        $group_total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'balance_in'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->sum('transaction_amount');

        $group_total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'balance_out'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->sum('transaction_amount');

        return response()->json([
            'transactions' => $transactions,
            'groupTotalDeposit' => $group_total_deposit,
            'groupTotalWithdrawal' => $group_total_withdrawal,
            'groupTotalNetBalance' => $group_total_deposit - $group_total_withdrawal,
        ]);
    }
}
