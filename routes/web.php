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
    });
    
    /**
     * ==============================
     *           Transaction
     * ==============================
     */
    Route::prefix('transaction')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('transaction');

        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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

        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    /**
     * ==============================
     *         Leaderboard
     * ==============================
     */
    Route::prefix('leaderboard')->group(function () {
        Route::get('/', [LeaderboardController::class, 'index'])->name('leaderboard');

        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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

        // Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
        // Route::post('/updateProfilePhoto', [ProfileController::class, 'updateProfilePhoto'])->name('profile.updateProfilePhoto');
        // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
