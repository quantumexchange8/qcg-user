<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Country;
use App\Models\Reward;
use App\Models\TradeRebateSummary;
use App\Models\SymbolGroup;
use App\Models\Transaction;
use App\Models\RewardRedemption;
use App\Models\TradePointHistory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RewardController extends Controller
{
    public function index()
    {
        return Inertia::render('Rewards/Rewards');
    }

    public function getCountryPhones()
    {
        $countries = Country::select('name', 'phone_code')->get();

        return response()->json([
            'countries' => $countries,
        ]);
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

    public function getRewardsData(Request $request)
    {
        $query = Reward::query();

        if ($request->filter == 'cash_rewards_only') {
            $query->where('type', 'cash_rewards');
        } elseif ($request->filter == 'physical_rewards_only') {
            $query->where('type', 'physical_rewards');
        }

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
        if ($request->reward_type == 'cash_rewards') {
            Validator::make($request->all(), [
                'meta_login' => 'required',
            ])->setAttributeNames([
                'meta_login' => trans('public.receiving_account'),
            ])->validate();
        } else {
            Validator::make($request->all(), [
                'recipient_name' => 'required',
                'dial_code' => 'required',
                'phone' => 'required',
                'phone_number' => 'required',
                'address' => 'required',
            ])->setAttributeNames([
                'recipient_name' => trans('public.recipient_name'),
                'dial_code' => trans('public.dial_code'),
                'phone' => trans('public.phone_number'),
                'phone_number' => trans('public.phone_number'),
                'address' => trans('public.address'),
            ])->validate();
        }

        // $transaction = RewardRedemption::create([
        //     'user_id' => Auth::id(),
        //     'category' => 'bonus',
        //     'transaction_type' => $request->bonus_type,
        //     'to_meta_login' => $tradingAccount->meta_login,
        //     'transaction_number' => RunningNumberService::getID('transaction'),
        //     'amount' => $claim_amount,
        //     'transaction_charges' => 0,
        //     'transaction_amount' => $claim_amount,
        //     'status' => 'processing',
        // ]);


        return redirect()->back()->with('notification', [
            // 'details' => $transaction,
            // 'type' => 'withdrawal',
            // 'withdrawal_type' => $transaction->category == 'rebate_wallet' ? 'rebate' : 'bonus'
        ]);
    }
}
