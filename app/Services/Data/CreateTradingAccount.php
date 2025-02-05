<?php

namespace App\Services\Data;

use App\Models\TradingAccount;
use App\Models\User;
use App\Models\AccountType;
use Illuminate\Support\Facades\DB;

class CreateTradingAccount
{
    public function execute(User $user, $data, AccountType $accountType): TradingAccount
    {
        return $this->storeNewAccount($user, $data, $accountType);
    }

    public function storeNewAccount(User $user, $data, AccountType $accountType): TradingAccount
    {
        $tradingAccount = new TradingAccount();
        $tradingAccount->user_id = $user->id;
        $tradingAccount->meta_login = $data['login'];
        $tradingAccount->currency_digits = $data['moneyDigits'];
        $tradingAccount->balance = $data['balance'] / 100;
        $tradingAccount->credit = $data['nonWithdrawableBonus'] / 100;
        $tradingAccount->margin_leverage = $data['leverageInCents'] / 100;
        $tradingAccount->equity = $data['equity'] / 100;
        $tradingAccount->account_type_id = $accountType->id;
        $tradingAccount->account_type = $accountType->id;
        $tradingAccount->status = 'active';

        $tradingAccount->promotion_title = $accountType->promotion_title ?? null;
        $tradingAccount->promotion_description = $accountType->promotion_description ?? null;
        $tradingAccount->promotion_period_type = $accountType->promotion_period_type ?? null;
        $tradingAccount->promotion_period = $accountType->promotion_period ?? null;
        $tradingAccount->promotion_type = $accountType->promotion_type ?? null;
        $tradingAccount->min_threshold = $accountType->min_threshold ?? null;
        $tradingAccount->bonus_type = $accountType->bonus_type ?? null;
        $tradingAccount->bonus_amount_type = $accountType->bonus_amount_type ?? null;
        $tradingAccount->bonus_amount = $accountType->bonus_amount ?? null;
        $tradingAccount->target_amount = $accountType->target_amount ?? null;
        $tradingAccount->applicable_deposit = $accountType->applicable_deposit ?? null;
        $tradingAccount->credit_withdraw_policy = $accountType->credit_withdraw_policy ?? null;
        $tradingAccount->credit_withdraw_date_period = $accountType->credit_withdraw_date_period ?? null;
        DB::transaction(function () use ($tradingAccount) {
            $tradingAccount->save();
        });

        return $tradingAccount;
    }
}
