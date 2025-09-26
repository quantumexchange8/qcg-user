<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Announcement;
use App\Models\Wallet;
use App\Models\ForumPost;
use App\Models\Transaction;
use App\Models\TradingAccount;
use Illuminate\Http\Request;
use App\Models\UserPostInteraction;
use App\Models\AnnouncementLog;
use App\Models\UserAnnouncementVisibility;
use App\Models\TradeBrokerHistory;
use App\Models\TeamHasUser;
use App\Models\TradeRebateSummary;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $announcements = Announcement::where('popup', true)
        ->where('status', 'active')
        ->where(function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('popup_login', 'every')
                  ->whereDoesntHave('read', function ($sub) use ($user) {
                      $sub->where('user_id', $user->id)
                          ->whereDate('date_read', now()->toDateString());
                  });
            })->orWhere(function ($q) use ($user) {
                $q->where('popup_login', 'first')
                  ->whereDoesntHave('read', function ($sub) use ($user) {
                      $sub->where('user_id', $user->id);
                  });
            });
        })
        ->get()
        ->map(function ($announcement) {
            $announcement->thumbnail = $announcement->getFirstMediaUrl('thumbnail');

            if ($announcement->recipient === 'selected_members') {

                $userHasVisibility = UserAnnouncementVisibility::where('announcement_id', $announcement->id)
                    ->where('user_id', Auth::id())
                    ->exists();

                if (!$userHasVisibility) {
                    return null;
                }
            }
            return $announcement;
        })
        ->filter()
        ->values();

        return Inertia::render('Dashboard/Dashboard', [
            'announcements' => $announcements,
        ]);

    }

    public function markAsViewed(Request $request)
    {
        $user = Auth::user();
        $id = $request->input('announcement_id');

        AnnouncementLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'announcementId' => $id,
            ],
            [
                'date_read' => now(),
            ]
        );


        return response()->json(['status' => 'ok']);
    }

    public function getPendingCounts()
    {
        $pendingTickets = Ticket::whereIn('status', ['new','in_progress'])->count();

        return response()->json([
            'pendingTickets' => $pendingTickets,
        ]);
    }

    public function getDashboardData()
    {
        $user = Auth::user();

        if ($user->role === 'agent') {
            $teamId = $user->teamHasUser->team_id ?? null;
        
            if ($teamId) {
                $teamUserIds = TeamHasUser::where('team_id', $teamId)->pluck('user_id')->toArray();
            } else {
                $teamUserIds = [];
            }
        
            $groupIds = $user->getChildrenIds();
            $groupIds[] = $user->id;
        
            $groupIds = array_intersect($groupIds, $teamUserIds);
        } else {
            $groupIds = [$user->id];
        }

        $rebate_wallet = $user->rebate_wallet;

        $standardAccountCondition = function ($direction) {
            return function ($query) use ($direction) {
                $query->whereHas($direction, function ($q) {
                    $q->whereHas('accountType', function ($q2) {
                        $q2->where('account_group', 'STANDARD.t');
                    });
                })->orWhere(function ($q) use ($direction) {
                    $column = $direction === 'toMetaLogin' ? 'to_meta_login' : 'from_meta_login';
                    $q->whereNull($column)
                      ->orWhereDoesntHave($direction);
                });
            };
        };
        
        // Group totals
        $group_total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'balance_in'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->where($standardAccountCondition('toMetaLogin'))
            ->sum('transaction_amount');
        
        $group_total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'balance_out'])
            ->where('status', 'successful')
            ->whereIn('user_id', $groupIds)
            ->where($standardAccountCondition('fromMetaLogin'))
            ->sum('amount');
        
        // Personal totals
        $personal_total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'balance_in'])
            ->where('status', 'successful')
            ->where('user_id', $user->id)
            ->where($standardAccountCondition('toMetaLogin'))
            ->sum('transaction_amount');
        
        $personal_total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'balance_out'])
            ->where('status', 'successful')
            ->where('user_id', $user->id)
            ->where($standardAccountCondition('fromMetaLogin'))
            ->sum('amount');

        $group_total_net_balance = TradingAccount::whereIn('user_id', $groupIds)
            ->sum(DB::raw('balance - credit'));

        $group_total_asset = TradingAccount::whereIn('user_id', $groupIds)
            ->sum('equity');

        if ($user->role === 'agent') {
            $group_total_trade_lots = TradeRebateSummary::where('upline_user_id', $user->id)
                ->whereNotNull('execute_at')
                ->sum('volume');
        } else {
            $group_total_trade_lots = TradeRebateSummary::where('user_id', $user->id)
                ->whereNotNull('execute_at')
                ->select('meta_login', 'closed_time', 'symbol_group_id', 'volume')
                ->distinct()
                ->get()
                ->sum('volume');
        }

        $group_total_trade_points = Wallet::where('user_id', $user->id)
        ->where('type', 'trade_points')
        ->sum('balance');
        // $group_total_trade_volume = TradeBrokerHistory::with('trading_account.ofUser')
        //     ->whereHas('trading_account.ofUser', function($query) use ($groupIds) {
        //         $query->whereIn('id', $groupIds);
        //     })
        //     ->sum('trade_volume');

        $pinned_announcements = Announcement::with([
            'media'
        ])
            ->where('pinned', true)
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(function ($announcement) {
                $announcement->thumbnail = $announcement->getFirstMediaUrl('thumbnail');

                if ($announcement->recipient === 'selected_members') {

                    $userHasVisibility = UserAnnouncementVisibility::where('announcement_id', $announcement->id)
                        ->where('user_id', Auth::id())
                        ->exists();

                    if (!$userHasVisibility) {
                        return null;
                    }
                }

                return $announcement;
            })
            ->filter()
            ->values();

        return response()->json([
            'rebateWallet' => $rebate_wallet,
            'pinnedAnnouncements' => $pinned_announcements,
            'personalTotalDeposit' => $personal_total_deposit,
            'personalTotalWithdrawal' => $personal_total_withdrawal,
            'groupTotalDeposit' => $group_total_deposit,
            'groupTotalWithdrawal' => $group_total_withdrawal,
            // 'groupTotalNetBalance' => $group_total_deposit - $group_total_withdrawal,
            'groupTotalNetBalance' => $group_total_net_balance,
            'groupTotalAsset' => $group_total_asset,
            'groupTotalTradeLot' => $group_total_trade_lots,
            'groupTotalTradePoints' => (float) $group_total_trade_points,
        ]);
    }

    public function getRebateEarnData()
    {
        $user = Auth::user();

        return response()->json([
            'rebateEarn' => $user->rebate_amount,
        ]);
    }

    public function admin_login(Request $request, $hashedToken)
    {
        $users = User::all();

        foreach ($users as $user) {

            $dataToHash = md5($user->first_name . $user->email . $user->id_number);
            if ($dataToHash === $hashedToken) {
                $admin_id = $request->admin_id;
                $admin_name = $request->admin_name;

                Activity::create([
                    'log_name' => 'access_portal',
                    'description' => $admin_name . ' with ID: ' . $admin_id . ' has access user ' . $user->first_name . ' with ID: ' . $user->id ,
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'causer_type' => User::class,
                    'causer_id' => $admin_id,
                    'event' => 'access_portal',
                ]);

                Auth::login($user);
                return redirect()->route('dashboard');
            }
        }

        return redirect()->route('login')->with('toast', [
            'title' => trans('public.access_denied'),
            'type' => 'error'
        ]);
    }
}
