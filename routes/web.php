<?php

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - accessible by all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes - accessible by all authenticated users
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Candidates - Export route HARUS sebelum resource routes
    Route::get('/candidates/export', [CandidateController::class, 'export'])
        ->middleware('role:admin,team_hc')
        ->name('candidates.export');

    // Candidates Resource Routes with role-based access
    Route::resource('candidates', CandidateController::class)->middleware('role:admin,team_hc,departemen');
    
    // Additional candidate routes - only admin and team_hc can modify
    Route::post('/candidates/{candidate}/update-stage', [CandidateController::class, 'updateStage'])
        ->middleware('role:admin,team_hc')
        ->name('candidates.updateStage');

    // Override some candidate routes for departemen role (read-only)
    Route::get('/candidates', [CandidateController::class, 'index'])
        ->middleware('role:admin,team_hc,departemen')
        ->name('candidates.index');
    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])
        ->middleware('role:admin,team_hc,departemen')
        ->name('candidates.show');

    // Import routes - only admin and team_hc
    Route::prefix('import')->name('import.')->middleware('role:admin,team_hc')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('/template/{type}', [ImportController::class, 'downloadTemplate'])->name('template');
    });

    // Statistics route - all authenticated users can view
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->middleware('role:admin,team_hc,departemen')
        ->name('statistics.index');

    // Accounts routes - only admin
    Route::prefix('accounts')->name('accounts.')->middleware('role:admin')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
    });
});

// Logout route
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');