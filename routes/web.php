<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Candidates
    Route::get('/candidates/export', [CandidateController::class, 'export'])
        ->middleware('role:admin,team_hc')
        ->name('candidates.export');

    Route::resource('candidates', CandidateController::class)
        ->middleware('role:admin,team_hc,departemen');

    Route::post('/candidates/{candidate}/update-stage', [CandidateController::class, 'updateStage'])
        ->middleware('role:admin,team_hc')
        ->name('candidates.updateStage');

    Route::get('/candidates', [CandidateController::class, 'index'])
        ->middleware('role:admin,team_hc,departemen')
        ->name('candidates.index');

    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])
        ->middleware('role:admin,team_hc,departemen')
        ->name('candidates.show');

    // ✅ Import Routes
    Route::prefix('import')->name('import.')->middleware('role:admin,team_hc')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/', [ImportController::class, 'store'])->name('process'); // Ganti 'store' ke 'process'
        Route::post('/process', [ImportController::class, 'store'])->name('store'); // Alias, jika masih ada form yang pakai 'store'
        Route::get('/template/{type?}', [ImportController::class, 'downloadTemplate'])->name('template');
        Route::get('/errors', function() {
            return view('import.errors', ['errors' => []]);
        })->name('errors');
    });

    // Statistics
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->middleware('role:admin,team_hc,departemen')
        ->name('statistics.index');

    // Accounts
    Route::prefix('accounts')->name('accounts.')->middleware('role:admin')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
    });
});

// Logout
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');
