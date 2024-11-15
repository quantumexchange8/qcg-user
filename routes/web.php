<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RebateSettingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\DownloadCenterController;

Route::get('locale/{locale}', function ($locale) {
    App::setLocale($locale);
    Session::put("locale", $locale);

    return redirect()->back();
});

Route::get('/', function () {
    return redirect(route('login'));
});

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    /**
     * ==============================
     *          Dashboard
     * ==============================
     */
    Route::prefix('dashboard')->group(function() {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/getDashboardData', [DashboardController::class, 'getDashboardData'])->name('getDashboardData');
        Route::get('/getRebateEarnData', [DashboardController::class, 'getRebateEarnData'])->name('getRebateEarnData');
        Route::get('/getPosts', [DashboardController::class, 'getPosts'])->name('member.getPosts');

        Route::post('/applyRebate', [TransactionController::class, 'applyRebate'])->name('dashboard.applyRebate');
        Route::post('/walletTransfer', [TransactionController::class, 'walletTransfer'])->name('dashboard.walletTransfer');
        Route::post('/walletWithdrawal', [TransactionController::class, 'walletWithdrawal'])->name('dashboard.walletWithdrawal');
        Route::get('/getRebateTransactions', [TransactionController::class, 'getRebateTransactions'])->name('dashboard.getRebateTransactions');
        Route::post('/createPost', [DashboardController::class, 'createPost'])->name('dashboard.createPost');
    });

    /**
     * ==============================
     *           Accounts
     * ==============================
     */
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('accounts');

        Route::post('/createTradingAccount', [AccountController::class, 'createTradingAccount'])->name('accounts.createTradingAccount');
        Route::get('/getTradingAccounts', [AccountController::class, 'getTradingAccounts'])->name('accounts.getTradingAccounts');
        Route::get('/getOptions', [AccountController::class, 'getOptions'])->name('accounts.getOptions');
        Route::get('/getAccountReport', [AccountController::class, 'getAccountReport'])->name('accounts.getAccountReport');
        Route::get('/getLiveAccount', [AccountController::class, 'getLiveAccount'])->name('accounts.getLiveAccount');
        Route::post('/create_live_account', [AccountController::class, 'create_live_account'])->name('accounts.create_live_account');
        Route::post('/create_demo_account', [AccountController::class, 'create_demo_account'])->name('accounts.create_demo_account');
        Route::post('/deposit_to_account', [AccountController::class, 'deposit_to_account'])->name('accounts.deposit_to_account');
        Route::post('/withdrawal_from_account', [AccountController::class, 'withdrawal_from_account'])->name('accounts.withdrawal_from_account');
        Route::post('/change_leverage', [AccountController::class, 'change_leverage'])->name('accounts.change_leverage');
        Route::post('/internal_transfer', [AccountController::class, 'internal_transfer'])->name('accounts.internal_transfer');
        Route::delete('/delete_account', [AccountController::class, 'delete_account'])->name('accounts.delete_account');
    });

    /**
     * ==============================
     *           Network
     * ==============================
     */
    Route::prefix('network')->group(function () {
        Route::get('/', [NetworkController::class, 'index'])->name('network');

        Route::get('/getDownlineData', [NetworkController::class, 'getDownlineData'])->name('network.getDownlineData');
        Route::get('/getDownlineListingData', [NetworkController::class, 'getDownlineListingData'])->name('network.getDownlineListingData');
        Route::get('/getFilterData', [NetworkController::class, 'getFilterData'])->name('network.getFilterData');
        Route::get('/downline/{id_number}', [NetworkController::class, 'viewDownline'])->name('network.viewDownline');
        Route::get('/getUserData', [NetworkController::class, 'getUserData'])->name('network.getUserData');
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
    Route::prefix('report')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('report');

        Route::get('/getGroupTransaction', [ReportController::class, 'getGroupTransaction'])->name('report.getGroupTransaction');
        Route::get('/getRebateBreakdown', [ReportController::class, 'getRebateBreakdown'])->name('report.getRebateBreakdown');
        Route::get('/getRebateDetails', [ReportController::class, 'getRebateDetails'])->name('report.getRebateDetails');
        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
    /**
     * ==============================
     *         Rebate Setting
     * ==============================
     */
    Route::prefix('rebate_setting')->group(function () {
        Route::get('/', [RebateSettingController::class, 'index'])->name('rebate_setting');

        Route::get('/getRebateData', [RebateSettingController::class, 'getRebateData'])->name('rebate_setting.getRebateData');
        Route::get('/getAgents', [RebateSettingController::class, 'getAgents'])->name('rebate_setting.getAgents');

        Route::post('/updateRebateAmount', [RebateSettingController::class, 'updateRebateAmount'])->name('rebate_setting.updateRebateAmount');
    });

    /**
     * ==============================
     *         Leaderboard
     * ==============================
     */
    Route::prefix('leaderboard')->group(function () {
        Route::get('/', [LeaderboardController::class, 'index'])->name('leaderboard');

        Route::get('/getTotalIncentiveGraph', [LeaderboardController::class, 'getTotalIncentiveGraph'])->name('leaderboard.getTotalIncentiveGraph');
        Route::get('/getWithdrawalHistory', [LeaderboardController::class, 'getWithdrawalHistory'])->name('leaderboard.getWithdrawalHistory');
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
    });
});

require __DIR__.'/auth.php';
