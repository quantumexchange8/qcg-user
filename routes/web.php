<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Session;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Route::get('/getPendingCounts', [DashboardController::class, 'getPendingCounts'])->name('dashboard.getPendingCounts');
    // Route::get('/getAccountData', [DashboardController::class, 'getAccountData'])->name('dashboard.getAccountData');
    // Route::get('/getPendingData', [DashboardController::class, 'getPendingData'])->name('dashboard.getPendingData');
    // Route::get('/getAssetData', [DashboardController::class, 'getAssetData'])->name('dashboard.getAssetData');

    /**
     * ==============================
     *           Accounts
     * ==============================
     */
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('accounts');

        Route::post('/createTradingAccount', [AccountController::class, 'createTradingAccount'])->name('accounts.createTradingAccount');
        Route::get('/getTradingAccounts', [AccountController::class, 'getTradingAccounts'])->name('accounts.getTradingAccounts');
        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    /**
     * ==============================
     *           Network
     * ==============================
     */
    Route::prefix('network')->group(function () {
        Route::get('/', [NetworkController::class, 'index'])->name('network');

        Route::get('/getDownlineData', [NetworkController::class, 'getDownlineData'])->name('network.getDownlineData');
        Route::get('/formatUserData', [NetworkController::class, 'formatUserData'])->name('network.formatUserData');
        Route::get('/calculateLevel', [NetworkController::class, 'calculateLevel'])->name('network.calculateLevel');
        Route::get('/getChildrenCount', [NetworkController::class, 'getChildrenCount'])->name('network.getChildrenCount');
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
        Route::post('/getAchievements', [LeaderboardController::class, 'getAchievements'])->name('leaderboard.getAchievements');
        Route::post('/getAgents', [LeaderboardController::class, 'getAgents'])->name('leaderboard.getAgents');
        Route::post('/getStatementData', [LeaderboardController::class, 'getStatementData'])->name('leaderboard.getStatementData');
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
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
