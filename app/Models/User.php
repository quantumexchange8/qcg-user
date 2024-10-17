<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, SoftDeletes, InteractsWithMedia, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function setReferralId(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = 'MOS';

        $length = 10 - strlen($randomString);

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $this->referral_code = $randomString;
        $this->save();
    }

    public function getChildrenIds(): array
    {
        return User::query()->where('hierarchyList', 'like', '%-' . $this->id . '-%')
            ->pluck('id')
            ->toArray();
    }

    public function directChildren(): HasMany
    {
        return $this->hasMany(User::class, 'upline_id', 'id');
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_id', 'id');
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'user_id', 'id');
    }

    public function tradingAccounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class, 'user_id', 'id');
    }

    public function tradingUsers(): HasMany
    {
        return $this->hasMany(TradingUser::class, 'user_id', 'id');
    }

    public function rebate_wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id')->where('type', 'rebate_wallet');
    }

    public function incentive_wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id')->where('type', 'incentive_wallet');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }

    public function rebateAllocations(): HasMany
    {
        return $this->hasMany(RebateAllocation::class, 'user_id', 'id');
    }

}
