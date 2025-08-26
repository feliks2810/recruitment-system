<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EventController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - NO PERMISSION MIDDLEWARE (all roles can access)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Calendar Events for Dashboard
    Route::get('/debug-calendar-events', [EventController::class, 'debugCalendarEvents'])->name('events.debug');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Events
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/calendar', [EventController::class, 'getCalendarEvents'])->name('calendar');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/today', [EventController::class, 'getTodayEvents'])->name('today');
        Route::get('/upcoming', [EventController::class, 'getUpcomingEvents'])->name('upcoming');
        Route::get('/by-date/range', [EventController::class, 'getEventsByDateRange'])->name('by-date');
        Route::get('/{event}', [EventController::class, 'show'])->name('show');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
    });

    // Candidates Group - Let controller handle authorization
    Route::prefix('candidates')->name('candidates.')->group(function () {
        // Basic candidate viewing (let controller handle department restrictions)
        Route::get('/', [CandidateController::class, 'index'])->name('index');
        Route::get('/{candidate}', [CandidateController::class, 'show'])->name('show');

        // Export functionality for Team HC
        Route::middleware('can:export-candidates')->group(function () {
            Route::get('/export', [CandidateController::class, 'export'])->name('export');
            Route::post('/bulk-export', [CandidateController::class, 'bulkExport'])->name('bulkExport');
        });

        // Candidate creation for Team HC
        Route::middleware('can:create-candidates')->group(function () {
            Route::get('/create', [CandidateController::class, 'create'])->name('create');
            Route::post('/', [CandidateController::class, 'store'])->name('store');
        });

        // Candidate management actions for Team HC
        Route::middleware('can:edit-candidates')->group(function () {
            Route::get('/{candidate}/edit', [CandidateController::class, 'edit'])->name('edit');
            Route::put('/{candidate}', [CandidateController::class, 'update'])->name('update');
            Route::post('/{candidate}/stage', [CandidateController::class, 'updateStage'])->name('updateStage');
            Route::patch('/{candidate}/toggle-duplicate', [CandidateController::class, 'toggleDuplicate'])->name('toggleDuplicate');
            Route::post('/{candidate}/switch-type', [CandidateController::class, 'switchType'])->name('switchType');
            Route::post('/bulk-update-status', [CandidateController::class, 'bulkUpdateStatus'])->name('bulkUpdateStatus');
            Route::post('/bulk-move-stage', [CandidateController::class, 'bulkMoveStage'])->name('bulkMoveStage');
            Route::post('/bulk-switch-type', [CandidateController::class, 'bulkSwitchType'])->name('bulkSwitchType');
            Route::post('/{candidate}/next-test-date', [CandidateController::class, 'setNextTestDate'])->name('setNextTestDate');
            Route::post('/check-duplicate', [CandidateController::class, 'checkDuplicate'])->name('checkDuplicate');
        });

        // Delete actions for CTeam H
        Route::middleware('can:delete-candidates')->group(function () {
            Route::delete('/{candidate}', [CandidateController::class, 'destroy'])->name('destroy');
            Route::delete('/bulk-actions/delete', [CandidateController::class, 'bulkDelete'])->name('bulkDelete');
        });
    });

    // Import Routes for Team HC
    Route::prefix('import')->name('import.')->middleware('can:import-excel')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/', [ImportController::class, 'store'])->name('process');
        Route::post('/process', [ImportController::class, 'store'])->name('store');
        Route::get('/template/{type?}', [ImportController::class, 'downloadTemplate'])->name('template');
        Route::get('/errors', function() {
            return view('import.errors', ['errors' => []]);
        })->name('errors');
    });

    // Statistics for Team HC and Department
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->middleware('can:view-statistics')
        ->name('statistics.index');

    // Reports for Team HC
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('can:view-reports')
        ->name('reports.export');

    // Account management for Admin only
    Route::prefix('accounts')->name('accounts.')->middleware('can:manage-users')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        Route::get('/export', [AccountController::class, 'export'])->name('export');
    });

    // Debug route for testing permissions
    Route::get('/check-auth', function () {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not logged in'], 401);
        }
        return response()->json([
            'user' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'can_view_dashboard' => $user->can('view-dashboard'),
            'can_view_candidates' => $user->can('view-candidates'),
            'can_view_reports' => $user->can('view-reports'),
            'can_import_excel' => $user->can('import-excel'),
            'can_manage_users' => $user->can('manage-users'),
        ]);
    });
});

