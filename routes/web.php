<?php

use Inertia\Inertia;
use App\Models\Transaction;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RebateSettingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\DownloadCenterController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\AnnouncementController;

Route::get('locale/{locale}', function ($locale) {
    App::setLocale($locale);
    Session::put("locale", $locale);

    return redirect()->back();
});

Route::get('/', function () {
    return redirect(route('login'));
});

Route::get('approval/{token}', function ($token) {
    $transactions = Transaction::with([
        'user:id,first_name,email',
        'media'
    ])
        ->where('transaction_type', 'deposit')
        ->latest()
        ->get();

    foreach ($transactions as $transaction) {
        $hashed_token = md5($transaction->user->email . $transaction->transaction_number);

        if ($token == $hashed_token) {
            if ($transaction->status == 'processing' && $transaction->comment) {
                $type = 'Missing Amount Approval';
            } else {
                $type = 'Spread Amount Approval';
            }

            return Inertia::render('DepositApproval', [
                'transaction' => $transaction,
                'type' => $type
            ]);
        }
    }

    // Handle case when no payment matches the token
    abort(503);
})->name('approval');

Route::post('depositApproval', [TransactionController::class, 'depositApproval'])->name('depositApproval');

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/admin_login/{hashedToken}', [DashboardController::class, 'admin_login']);
Route::post('deposit_callback', [AccountController::class, 'depositCallback'])->name('depositCallback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('deposit_return', [AccountController::class, 'depositReturn'])->name('depositReturn');

    Route::get('/getTransactionMonths', [GeneralController::class, 'getTransactionMonths'])->name('getTransactionMonths');
    Route::get('/getTradeMonths', [GeneralController::class, 'getTradeMonths'])->name('getTradeMonths');
    Route::get('/getIncentiveMonths', [GeneralController::class, 'getIncentiveMonths'])->name('getIncentiveMonths');

    /**
     * ==============================
     *             Forum
     * ==============================
     */
    Route::prefix('forum')->group(function() {
        Route::get('/', [ForumController::class, 'index'])->name('forum');
        Route::get('/getPosts', [ForumController::class, 'getPosts'])->name('forum.getPosts');
        Route::post('/createPost', [ForumController::class, 'createPost'])->name('forum.createPost');
        Route::post('/postInteraction', [ForumController::class, 'postInteraction'])->name('forum.postInteraction');
    });
    /**
     * ==============================
     *          Dashboard
     * ==============================
     */
    Route::prefix('dashboard')->group(function() {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/markAsViewed', [DashboardController::class, 'markAsViewed'])->name('markAsViewed');
        Route::get('/getDashboardData', [DashboardController::class, 'getDashboardData'])->name('getDashboardData');
        Route::get('/getRebateEarnData', [DashboardController::class, 'getRebateEarnData'])->name('getRebateEarnData');

        Route::post('/applyRebate', [TransactionController::class, 'applyRebate'])->name('dashboard.applyRebate');
        Route::post('/walletTransfer', [TransactionController::class, 'walletTransfer'])->name('dashboard.walletTransfer');
        Route::post('/walletWithdrawal', [TransactionController::class, 'walletWithdrawal'])->name('dashboard.walletWithdrawal');
        Route::get('/getRebateTransactions', [TransactionController::class, 'getRebateTransactions'])->name('dashboard.getRebateTransactions');
    });


    /**
     * ==============================
     *           Highlights
     * ==============================
     */
    Route::prefix('highlights')->group(function() {
        Route::get('/', [AnnouncementController::class, 'index'])->name('highlights');
        Route::get('/getAnnouncement', [AnnouncementController::class, 'getAnnouncement'])->name('highlights.getAnnouncement');
        // Route::get('/', [AnnouncementController::class, ''])->name('highlights.');
        // Route::get('/', [AnnouncementController::class, ''])->name('highlights.');
        // Route::get('/', [AnnouncementController::class, ''])->name('highlights.');
    });


    /**
     * ==============================
     *           Accounts
     * ==============================
     */
    Route::prefix('accounts')->group(function () {
        // Route::get('/', [AccountController::class, 'index'])->name('accounts');

        Route::post('/createTradingAccount', [AccountController::class, 'createTradingAccount'])->name('accounts.createTradingAccount');
        Route::get('/getTradingAccounts', [AccountController::class, 'getTradingAccounts'])->name('accounts.getTradingAccounts');
        Route::get('/getOptions', [AccountController::class, 'getOptions'])->name('accounts.getOptions');
        Route::get('/getAccountReport', [AccountController::class, 'getAccountReport'])->name('accounts.getAccountReport');
        Route::get('/getLiveAccount', [AccountController::class, 'getLiveAccount'])->name('accounts.getLiveAccount');
        Route::post('/create_live_account', [AccountController::class, 'create_live_account'])->name('accounts.create_live_account');
        Route::post('/create_demo_account', [AccountController::class, 'create_demo_account'])->name('accounts.create_demo_account');
        Route::post('/deposit_to_account', [AccountController::class, 'deposit_to_account'])->name('accounts.deposit_to_account');
        Route::post('/accountWithdrawal', [AccountController::class, 'accountWithdrawal'])->name('accounts.accountWithdrawal');
        Route::post('/change_leverage', [AccountController::class, 'change_leverage'])->name('accounts.change_leverage');
        Route::post('/internal_transfer', [AccountController::class, 'internal_transfer'])->name('accounts.internal_transfer');
        Route::post('/missing_amount', [AccountController::class, 'missing_amount'])->name('accounts.missing_amount');
        Route::delete('/delete_account', [AccountController::class, 'delete_account'])->name('accounts.delete_account');
        Route::post('/claim_bonus', [AccountController::class, 'claim_bonus'])->name('accounts.claim_bonus');
    });

    /**
     * ==============================
     *           Network
     * ==============================
     */
    Route::prefix('network')->group(function () {
        Route::get('/', [NetworkController::class, 'index'])->name('network');

        Route::get('/getDownlineData', [NetworkController::class, 'getDownlineData'])->name('network.getDownlineData');

        Route::middleware('role:agent')->group(function () {
            Route::get('/getDownlineListingData', [NetworkController::class, 'getDownlineListingData'])->name('network.getDownlineListingData');
            Route::get('/getFilterData', [NetworkController::class, 'getFilterData'])->name('network.getFilterData');
            Route::get('/downline/{id_number}', [NetworkController::class, 'viewDownline'])->name('network.viewDownline');
            Route::get('/getUserData', [NetworkController::class, 'getUserData'])->name('network.getUserData');
        });
    });

    /**
     * ==============================
     *           Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('transaction');

        Route::get('/getTransactionHistory', [TransactionController::class, 'getTransactionHistory'])->name('transaction.getTransactionHistory');

    });

    /**
     * ==============================
     *           Report
     * ==============================
     */
    Route::prefix('report')->middleware('role:agent')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('report');

        Route::get('/getGroupTransaction', [ReportController::class, 'getGroupTransaction'])->name('report.getGroupTransaction');
        Route::get('/getRebateBreakdown', [ReportController::class, 'getRebateBreakdown'])->name('report.getRebateBreakdown');
        Route::get('/getRebateDetails', [ReportController::class, 'getRebateDetails'])->name('report.getRebateDetails');
    });

    /**
     * ==============================
     *           Rewards
     * ==============================
     */
    Route::prefix('rewards')->group(function () {
        Route::get('/', [RewardController::class, 'index'])->name('rewards');

        Route::get('/getTradePoints', [RewardController::class, 'getTradePoints'])->name('rewards.getTradePoints');
        Route::get('/getPointHistory', [RewardController::class, 'getPointHistory'])->name('rewards.getPointHistory');

        Route::get('/getRewardsData', [RewardController::class, 'getRewardsData'])->name('rewards.getRewardsData');
        Route::get('/getCountryPhones', [RewardController::class, 'getCountryPhones'])->name('rewards.getCountryPhones');
        Route::post('/redeemRewards', [RewardController::class, 'redeemRewards'])->name('rewards.redeemRewards');
        Route::get('/getRedeemHistory', [RewardController::class, 'getRedeemHistory'])->name('rewards.getRedeemHistory');

    });

    /**
     * ==============================
     *         Rebate Setting
     * ==============================
     */
//    Route::prefix('rebate_setting')->middleware('role:agent')->group(function () {
//        Route::get('/', [RebateSettingController::class, 'index'])->name('rebate_setting');
//
//        Route::get('/getRebateData', [RebateSettingController::class, 'getRebateData'])->name('rebate_setting.getRebateData');
//        Route::get('/getAgents', [RebateSettingController::class, 'getAgents'])->name('rebate_setting.getAgents');
//
//        Route::post('/updateRebateAmount', [RebateSettingController::class, 'updateRebateAmount'])->name('rebate_setting.updateRebateAmount');
//    });

    /**
     * ==============================
     *         Leaderboard
     * ==============================
     */
    Route::prefix('leaderboard')->middleware('role:agent')->group(function () {
        Route::get('/', [LeaderboardController::class, 'index'])->name('leaderboard');

        Route::get('/getTotalIncentiveData', [LeaderboardController::class, 'getTotalIncentiveData'])->name('leaderboard.getTotalIncentiveData');
        Route::get('/getIncentiveData', [LeaderboardController::class, 'getIncentiveData'])->name('leaderboard.getIncentiveData');
        Route::get('/getWithdrawalHistory', [LeaderboardController::class, 'getWithdrawalHistory'])->name('leaderboard.getWithdrawalHistory');
        Route::post('/incentiveWithdrawal', [LeaderboardController::class, 'incentiveWithdrawal'])->name('leaderboard.incentiveWithdrawal');
        Route::get('/getAchievements', [LeaderboardController::class, 'getAchievements'])->name('leaderboard.getAchievements');
        Route::get('/getAgents', [LeaderboardController::class, 'getAgents'])->name('leaderboard.getAgents');
        Route::get('/getStatementData', [LeaderboardController::class, 'getStatementData'])->name('leaderboard.getStatementData');
    });

    /**
     * ==============================
     *         Download Center
     * ==============================
     */
    Route::prefix('download_center')->group(function () {
        Route::get('/', [DownloadCenterController::class, 'index'])->name('download_center');

        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    /**
     * ==============================
     *           Profile
     * ==============================
     */
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('profile');

        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        Route::post('/updateCryptoWalletInfo', [ProfileController::class, 'updateCryptoWalletInfo'])->name('profile.updateCryptoWalletInfo');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/getKycVerification', [ProfileController::class, 'getKycVerification'])->name('profile.getKycVerification');
        Route::post('/updateKyc', [ProfileController::class, 'updateKyc'])->name('profile.updateKyc');
    });
});

require __DIR__.'/auth.php';
