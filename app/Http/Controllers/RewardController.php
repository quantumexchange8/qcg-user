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
use App\Models\User;
use App\Models\Wallet;
use App\Services\RunningNumberService;
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
        $user = Auth::user();
        $total_points = $user->trade_points->balance;
        $userId = $user->id;
        // $userId = Auth::id();

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
            'totalTradePoints' => (float) $total_points,
            // 'totalVolume' => $totalVolume,
            // 'totalRebate' => $totalRebate,
        ]);
    }

    public function getPointHistory()
    {

    }

    public function getRewardsData(Request $request)
    {
        $userId = Auth::id();

        $query = Reward::withCount(['redemption as redemption_count' => function ($query) use ($userId) {
            $query->where('user_id', $userId);
        }]);

        if ($request->filter == 'cash_rewards_only') {
            $query->where('type', 'cash_rewards');
        } elseif ($request->filter == 'physical_rewards_only') {
            $query->where('type', 'physical_rewards');
        }

        $rewards = $query->get()
            ->map(function ($reward) {
                $name = json_decode($reward->name, true);
                $reward_thumbnail = $reward->getFirstMediaUrl('reward_thumbnail');

                $current_status = 'redeem';

                if ($reward->status == 'active') {
                    $current_status = 'redeem';
                    if (!is_null($reward->maximum_redemption) && $reward->redemption_count >= $reward->maximum_redemption) {
                        $current_status = 'fully_redeemed';
                    }
                } else {
                    $current_status = 'coming_soon';
                    if ($reward->expiry_date && Carbon::now()->greaterThan($reward->expiry_date)) {
                        $current_status = 'expired';
                    }
                }

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
                    'current_status' => $current_status,
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
        $user = Auth::user();
        $wallet = $user->trade_points;

        if ($request->reward_type == 'cash_rewards') {
            Validator::make($request->all(), [
                'meta_login' => 'required',
            ])->setAttributeNames([
                'meta_login' => trans('public.receiving_account'),
            ])->validate();

            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $request->reward_id,
                'receiving_account' => $request->meta_login,
                'status' => 'processing',
            ]);    

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'category' => 'trade_points',
                'transaction_type' => 'redemption',
                'from_wallet_id' => $wallet->id,
                'to_meta_login' => $request->meta_login,
                'redemption_id' => $redemption->id,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'amount' => $redemption->reward->trade_point_required,
                'transaction_charges' => 0,
                'transaction_amount' => $redemption->reward->trade_point_required,
                'status' => 'processing',
            ]);

            $wallet->update(['balance' => $wallet->balance - $transaction->amount]);

            $redemption->reward->name = json_decode($redemption->reward->name, true);

            return redirect()->back()->with('notification', [
                'details' => $redemption,
                'type' => 'redeem_cash_success',
            ]);

            // return redirect()->back()->with('toast', [
            //     'title' => 'Successfully redeemed cash reward!',
            //     'type' => 'success',
            // ]);
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

            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $request->reward_id,
                'recipient_name' => $request->recipient_name,
                'dial_code' => $request->dial_code,
                'phone' => $request->phone,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'status' => 'processing',
            ]);
    
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'category' => 'trade_points',
                'transaction_type' => 'redemption',
                'from_wallet_id' => $wallet->id,
                'to_meta_login' => $request->meta_login,
                'redemption_id' => $redemption->id,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'amount' => $redemption->reward->trade_point_required,
                'transaction_charges' => 0,
                'transaction_amount' => $redemption->reward->trade_point_required,
                'status' => 'processing',
            ]);

            $wallet->update(['balance' => $wallet->balance - $transaction->amount]);

            $redemption->reward->name = json_decode($redemption->reward->name, true);

            return redirect()->back()->with('notification', [
                'details' => $redemption,
                'type' => 'redeem_physical_success',
            ]);

            // return redirect()->back()->with('toast', [
            //     'title' => 'Successfully redeemed cash reward!',
            //     'type' => 'success',
            // ]);
        }

    }
}
