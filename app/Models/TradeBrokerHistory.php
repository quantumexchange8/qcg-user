<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeBrokerHistory extends Model
{
    use HasFactory;

    public function trading_account()
    {
        return $this->belongsTo(TradingAccount::class, 'meta_login', 'meta_login');
    }

    public function user()
    {
        return $this->belongsToThrough(User::class, TradingAccount::class);
    }
}
