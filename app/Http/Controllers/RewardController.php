<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Reward;
use App\Models\TradeRebateSummary;
use App\Models\SymbolGroup;
use App\Models\Transaction;

class RewardController extends Controller
{
    public function index()
    {
        return Inertia::render('Rewards/Rewards');
    }

    public function getTradePoints()
    {
        $userId = Auth::id();

        // Initialize query for rebate summary with date filtering
        $query = TradeRebateSummary::with('symbolGroup')
            ->where('upline_user_id', $userId)
            ->whereNotNull('execute_at');

        // Fetch rebate summary data
        $rebateSummary = $query->get(['symbol_group_id', 'volume', 'rebate']);

        // $rebateSummary = $query->get(['symbol_group_id', 'trade_points']);

        // Retrieve all symbol groups with non-null display values
        $symbolGroups = SymbolGroup::whereNotNull('display')->pluck('display', 'id');

        // Aggregate rebate data in PHP
        $rebateSummaryData = $rebateSummary->groupBy('symbol_group_id')->map(function ($items) {
            return [
                'volume' => $items->sum('volume'),
                'rebate' => $items->sum('rebate'),

                // '$trade_points' => $items->sum('trade_points'),
            ];
        });

        // Initialize final summary and totals
        $finalSummary = [];
        $totalVolume = 0;
        $totalRebate = 0;

        // $totalTradePoints = 0;

        // Iterate over all symbol groups
        foreach ($symbolGroups as $id => $display) {
            // Retrieve data or use default values
            $data = $rebateSummaryData->get($id);

            // $data = $rebateSummaryData->get($id, ['trade_points' => 0]);

            // Add to the final summary
            $finalSummary[] = [
                'symbol_group' => $display,
                'volume' => 0, //$data['volume'],
                'rebate' => 0, //$data['rebate'],

                // 'trade_points' => $data['trade_points'],
            ];

            // Accumulate totals
            // $totalVolume += $data['volume'];
            // $totalRebate += $data['rebate'];
        }

        // Return the response with rebate summary, total volume, and total rebate
        return response()->json([
            'tradePoints' => $finalSummary,
            // 'totalVolume' => $totalVolume,
            // 'totalRebate' => $totalRebate,
        ]);
    }

    public function getPointHistory()
    {

    }

    public function getRewardsData()
    {
        $query = Reward::query();

        // if ($request->filter == 'most_redeemed') {
        //     $query->orderByDesc('trade_point_required');
        // } elseif ($request->filter == 'cash_rewards_only') {
        //     $query->where('type', 'cash_rewards');
        // } elseif ($request->filter == 'physical_rewards_only') {
        //     $query->where('type', 'physical_rewards');
        // } else {
        //     $query->orderBy('trade_point_required');
        // }

        $rewards = $query->get()
            ->map(function ($reward) {
                $name = json_decode($reward->name, true);
                $reward_thumbnail = $reward->getFirstMediaUrl('reward_thumbnail');

                return [
                    'reward_id' => $reward->id,
                    'type' => $reward->type,
                    'code' => $reward->code,
                    'trade_point_required' => $reward->trade_point_required,
                    // 'start_date' => $reward->start_date,
                    'expiry_date' => $reward->expiry_date,
                    'maximum_redemption' => $reward->maximum_redemption,
                    'autohide_after_expiry' => $reward->autohide_after_expiry,
                    'status' => $reward->status,
                    'name' => $name,
                    'reward_thumbnail' => $reward_thumbnail,
                ];
            })
            ->values(); 

        return response()->json([
            'rewards' => $rewards,
        ]);
    }

    public function redeemRewards(Request $request)
    {
        
    }
}
