<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\Group;
use App\Models\Country;
use App\Models\TeamHasUser;
use App\Models\Transaction;
use App\Models\GroupHasUser;
use App\Models\SettingLeverage;
use App\Models\PaymentAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DropdownOptionService
{
    public function getUplines(): Collection
    {
        return User::whereIn('id', User::find(Auth::id())->getChildrenIds())
            ->select('id', 'first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                    'profile_photo' => $user->getFirstMediaUrl('profile_photo')
                ];
            });
    }

    public function getCountries(): Collection
    {
        return Country::get()->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'phone_code' => $country->phone_code,
            ];
        });
    }

    public function getTeams(): Collection
    {
        return Team::get()
            ->map(function ($team) {
                return [
                    'value' => $team->id,
                    'name' => $team->name,
                    'color' => $team->color,
                ];
            });
    }

    public function getLeverages(): Collection
    {
        $leverages = SettingLeverage::where('status', 'active')
                    ->get()
                    ->map(function ($leverage) {
                        return [
                            'name' => $leverage->display,
                            'value' => $leverage->value,
                        ];
                    });
        $leverages->prepend(['name' => 'Free', 'value' => 0]);
        return $leverages;
    }

    public function getAgents(): Collection
    {
        $has_team = TeamHasUser::pluck('user_id');

        $users = User::where('role', 'agent')
            ->whereNotIn('id', $has_team)
            ->select('id', 'first_name')
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                    'profile_photo' => $user->getFirstMediaUrl('profile_photo')
                ];
            });

        return $users;
    }

    public function getTransactionMonths(): Collection
    {
        // Fetch the created_at dates of all transactions
        $transactionDates = Transaction::pluck('created_at');

        // Map the dates to the desired format and remove duplicates
        $months = $transactionDates
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('m/Y');
            })
            ->unique()
            ->values();

        return $months;
    }

    public function getLeveragesOptions(): Collection
    {
        $leverages = SettingLeverage::where('status', 'active')
                    ->get()
                    ->map(function ($leverage) {
                        return [
                            'name' => $leverage->leverage,
                            'value' => $leverage->value,
                        ];
                    });
        return $leverages;
    }

    public function getInternalTransferOptions(): Collection
    {
        $user = Auth::user();

        $trading_accounts = $user->tradingAccounts;
        try {
            foreach ($trading_accounts as $trading_account) {
                (new CTraderService)->getUserInfo($trading_account->meta_login);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

        return $user->tradingAccounts->map(function($trading_account) {
            return [
                'name' => $trading_account->meta_login,
                'value' => $trading_account->balance,
            ];
        });
    }

    public function getWalletOptions(): Collection
    {
        return PaymentAccount::where('user_id', Auth::id())
                    ->get()
                    ->map(function ($walletOption) {
                        return [
                            'name' => $walletOption->payment_account_name,
                            'value' => $walletOption->account_no,
                        ];
                    });
    }

}
