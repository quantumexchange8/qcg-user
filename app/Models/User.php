<?php

namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ForumPost;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
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

    protected $appends = ['has_new_forum_posts', 'has_new_ticket_replies', 'ticket_agent_access'];

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

    public function sendEmailVerificationNotification()
    {
        $this->notify((new QueuedVerifyEmail)->onQueue('verify_email'));
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

    public function assignedTeam($team_id): void
    {
        TeamHasUser::updateOrCreate(
            ['user_id' => $this->id],
            ['team_id' => $team_id]
        );
    }

    // Relations
    public function teamHasUser(): HasOne
    {
        return $this->hasOne(TeamHasUser::class, 'user_id');
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

    public function trade_points(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id')->where('type', 'trade_points');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }

    public function rebateAllocations(): HasMany
    {
        return $this->hasMany(RebateAllocation::class, 'user_id', 'id');
    }

    public function interactions()
    {
        return $this->hasMany(UserPostInteraction::class, 'user_id'); 
    }

    public function userAccountTypeVisibilities()
    {
        return $this->hasMany(UserAccountTypeVisibility::class);
    }

    public function read()
    {
        return $this->hasMany(AnnouncementLog::class, 'user_id');
    }

    public function read_ticket()
    {
        return $this->hasMany(TicketLog::class, 'user_id');
    }

    public function getHasNewForumPostsAttribute(): bool
    {
        $lastVisit = $this->forum_last_visit ?? now()->subYear();
        
        return ForumPost::where('created_at', '>', $lastVisit)->exists();
    }

    public function getHasNewTicketRepliesAttribute(): bool
    {
        $query = Ticket::with('replies')
            ->where('user_id', Auth::id())
            ->where('status', 'in_progress');

        $tickets = $query->get();

        $hasAdminReply = $tickets->contains(function ($ticket) {
            $lastReply = $ticket->replies->sortByDesc('created_at')->first();
            return $lastReply && $lastReply->user_id !== $ticket->user_id;
        });

        return $hasAdminReply;
    }

    public function getTicketAgentAccessAttribute(): string
    {
        return TicketAgentAccessibility::where('user_id', Auth::id())
            ->value('access_level') ?? 'none';
    }
}
