<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\AccountType;
use Illuminate\Http\Request;
use App\Models\RebateAllocation;
use Illuminate\Support\Facades\Auth;
use App\Models\AccountTypeSymbolGroup;
use App\Http\Controllers\GeneralController;
use Illuminate\Support\Facades\Validator;

class RebateSettingController extends Controller
{
    public function index()
    {
        return Inertia::render('RebateSetting/RebateSetting', [
            'accountTypes' => (new GeneralController())->getAccountTypes(true),
        ]);
    }

    public function getRebateData(Request $request)
    {
        $user = Auth::user();

        // Fetch rebate details
        $rebate_details = $user->rebateAllocations()
        ->where('account_type_id', $request->account_type_id)
        ->with(['symbol_group:id,display'])  // Eager load symbol_group relation
        ->get();

        return response()->json([
            'rebateDetails' => $rebate_details
        ]);
    }

    private function getChildrenCount($user_id): int
    {
        return User::where('role', 'agent')
            ->where('hierarchyList', 'like', '%-' . $user_id . '-%')
            ->count();
    }

    private function getHierarchyLevels($upline, $user_id)
    {
        $users = User::whereIn('id', $upline->getChildrenIds())->get();
        $minLevel = PHP_INT_MAX;
        $maxLevel = PHP_INT_MIN;

        foreach ($users as $user) {
            $levels = explode('-', trim($user->hierarchyList, '-'));
            if (in_array($user_id, array_map('intval', $levels))) {
                $levelCount = count($levels);
                $minLevel = min($minLevel, $levelCount);
                $maxLevel = max($maxLevel, $levelCount);
            }
        }

        return [
            'min' => $minLevel == PHP_INT_MAX ? 0 : $minLevel,
            'max' => $maxLevel == PHP_INT_MIN ? 0 : $maxLevel
        ];
    }

    public function getAgents(Request $request)
    {
        $type_id = $request->type_id;
        $user_id = Auth::id();
        //level 1 children
        $lv1_agents = User::where('upline_id', $user_id)->where('role', 'agent')
            ->get()->map(function($agent) {
                return [
                    'id' => $agent->id,
                    // 'profile_photo' => $agent->getFirstMediaUrl('profile_photo'),
                    'name' => $agent->first_name,
                    'email' => $agent->email,
                    'hierarchy_list' => $agent->hierarchyList,
                    'upline_id' => $agent->upline_id,
                    'level' => 1,
                ];
            })->toArray();

        $agents_array = array_map(function($agent) use ($type_id) {
            // Fetch rebate for each agent
            $rebate = $this->getRebateAllocate($agent['id'], $type_id);
            
            // Return the combined data of agent and rebate
            return [
                'agent_id' => $agent['id'],
                'agent_data' => $agent,
                'rebate_allocate' => $rebate,
            ];
        }, $lv1_agents);
        
        return response()->json($agents_array);

    }

    private function getRebateAllocate($user_id, $type_id)
    {
        $user = User::find($user_id);
        $rebate = $user->rebateAllocations()->where('account_type_id', $type_id)->get();
        $upline_rebate = User::find($user->upline_id)->rebateAllocations()->where('account_type_id', $type_id)->get();

        // Check if rebate is empty
        if ($rebate->isEmpty()) {
            // Handle the case when rebate data is not found
            return [];
        }
        
        $rebates = [
            'user_id' => $rebate[0]->user_id,
            'account_type_id' => $type_id,
            $rebate[0]->symbol_group_id => floatval($rebate[0]->amount),
            $rebate[1]->symbol_group_id => floatval($rebate[1]->amount),
            $rebate[2]->symbol_group_id => floatval($rebate[2]->amount),
            $rebate[3]->symbol_group_id => floatval($rebate[3]->amount),
            $rebate[4]->symbol_group_id => floatval($rebate[4]->amount),
            'upline_forex' => floatval($upline_rebate[0]->amount),
            'upline_stocks' => floatval($upline_rebate[1]->amount),
            'upline_indices' => floatval($upline_rebate[2]->amount),
            'upline_commodities' => floatval($upline_rebate[3]->amount),
            'upline_cryptocurrency' => floatval($upline_rebate[4]->amount),
        ];

        $downline = $user->directChildren()->where('role', 'agent')->first();

        if ($downline) {
            $downline_rebate = User::find($downline->id)->rebateAllocations()->where('account_type_id', $type_id)->get();

            if (!$downline_rebate->isEmpty()) {
                $rebates += [
                    'downline_forex' => floatval($downline_rebate[0]->amount),
                    'downline_stocks' => floatval($downline_rebate[1]->amount),
                    'downline_indices' => floatval($downline_rebate[2]->amount),
                    'downline_commodities' => floatval($downline_rebate[3]->amount),
                    'downline_cryptocurrency' => floatval($downline_rebate[4]->amount),
                ];
            }
        }

        return $rebates;
    }

    public function updateRebateAmount(Request $request)
    {
        $data = $request->rebates;
        \Log::info('Request Data:', $request->all());
        $rules = [];
        $attributeNames = [];

        $uplineFields = [
            '1' => 'upline_forex',
            '2' => 'upline_stocks',
            '3' => 'upline_indices',
            '4' => 'upline_commodities',
            '5' => 'upline_cryptocurrency',
        ];

        foreach ($uplineFields as $rebateKey => $uplineKey) {
            if (isset($data[$rebateKey]) && isset($data[$uplineKey])) {
                $rules["rebates.$rebateKey"] = [
                    'required',
                    'numeric',
                    function ($attribute, $value, $fail) use ($data, $uplineKey) {
                        \Log::info("Validating $attribute with value $value against {$data[$uplineKey]}");
                        if ($value > $data[$uplineKey]) {
                            $fail('Exceeds upline’s rebate.');
                        }
                    },
                ];
            }
        }
    
        $validator = Validator::make($request->all(), $rules);
        \Log::info('Validation Rules:', $rules);
        $validator->validate();

        $rebates = RebateAllocation::where('user_id', $data['user_id'])
            ->where('account_type_id', $data['account_type_id'])
            ->get();

        foreach ($rebates as $rebate) {
            if (isset($data[$rebate->symbol_group_id])) {
                $rebate->amount = $data[$rebate->symbol_group_id];
                $rebate->save();
            }
        }

        return back()->with('toast', [
            'title' => trans('public.toast_update_rebate_success_message'),
            'type' => 'success',
        ]);
    }
}
