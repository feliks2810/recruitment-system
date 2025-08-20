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
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

    // Candidates
    Route::get('/candidates/export', [CandidateController::class, 'export'])
        ->middleware('can:view-candidates')
        ->name('candidates.export');

    Route::get('/candidates', [CandidateController::class, 'index'])
        ->middleware('can:view-candidates')
        ->name('candidates.index');

    Route::post('/candidates/{candidate}/stage', [CandidateController::class, 'updateStage'])
        ->middleware('can:edit-candidates')
        ->name('candidates.updateStage');

    Route::get('/candidates/create', [CandidateController::class, 'create'])
        ->middleware('can:edit-candidates')
        ->name('candidates.create');

    Route::post('/candidates/check-duplicate', [CandidateController::class, 'checkDuplicate'])
        ->middleware('can:edit-candidates')
        ->name('candidates.checkDuplicate');

    Route::post('/candidates', [CandidateController::class, 'store'])
        ->middleware('can:edit-candidates')
        ->name('candidates.store');

    Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])
        ->middleware('can:show-candidates')
        ->name('candidates.show');

    Route::get('/candidates/{candidate}/edit', [CandidateController::class, 'edit'])
        ->middleware('can:edit-candidates')
        ->name('candidates.edit');

    Route::put('/candidates/{candidate}', [CandidateController::class, 'update'])
        ->middleware('can:edit-candidates')
        ->name('candidates.update');

    Route::delete('/candidates/{candidate}', [CandidateController::class, 'destroy'])
        ->middleware('can:delete-candidates')
        ->name('candidates.destroy');

    

    Route::patch('/candidates/{candidate}/update-stage', [CandidateController::class, 'updateStage'])
        ->middleware('can:edit-candidates')
        ->name('candidates.updateStage');

    Route::patch('/candidates/{candidate}/toggle-duplicate', [CandidateController::class, 'toggleDuplicate'])
        ->middleware('can:edit-candidates')
        ->name('candidates.toggleDuplicate');

    Route::post('/candidates/{candidate}/switch-type', [CandidateController::class, 'switchType'])
        ->middleware('can:edit-candidates')
        ->name('candidates.switchType');

    // Bulk Operations Routes
    Route::post('/candidates/bulk-update-status', [CandidateController::class, 'bulkUpdateStatus'])
        ->middleware('can:edit-candidates')
        ->name('candidates.bulkUpdateStatus');

    Route::post('/candidates/bulk-move-stage', [CandidateController::class, 'bulkMoveStage'])
        ->middleware('can:edit-candidates')
        ->name('candidates.bulkMoveStage');

    Route::delete('/candidates/bulk-actions/delete', [CandidateController::class, 'bulkDelete'])
        ->middleware('can:delete-candidates')
        ->name('candidates.bulkDelete');

    Route::post('/candidates/bulk-export', [CandidateController::class, 'bulkExport'])
        ->middleware('can:view-candidates')
        ->name('candidates.bulkExport');

    Route::post('/candidates/bulk-switch-type', [CandidateController::class, 'bulkSwitchType'])
        ->middleware('can:edit-candidates')
        ->name('candidates.bulkSwitchType');

    // ✅ Import Routes
    Route::prefix('import')->name('import.')->middleware('can:import-excel')->group(function () {
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
        ->middleware('can:view-statistics')
        ->name('statistics.index');

    // Reports
    Route::get('/reports/export', [ReportController::class, 'export'])
        ->middleware('can:view-reports')
        ->name('reports.export');

    // Accounts
    Route::prefix('accounts')->name('accounts.')->middleware('can:manage-users')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('/create', [AccountController::class, 'create'])->name('create');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::get('/{account}/edit', [AccountController::class, 'edit'])->name('edit');
        Route::put('/{account}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{account}', [AccountController::class, 'destroy'])->name('destroy');
        Route::get('/export', [AccountController::class, 'export'])->name('export');
    });

    // Debug route
    Route::get('/check-auth', function () {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not logged in'], 401);
        }
        return response()->json([
            'user' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'can_view_reports' => $user->can('view-reports'),
        ]);
    });

    Route::post('candidates/{candidate}/next-test-date', [CandidateController::class, 'setNextTestDate'])->name('candidates.setNextTestDate');
});

// Logout
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');