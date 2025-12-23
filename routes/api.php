<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DepartmentController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    /** @var \App\Models\User $user */
    $user = $request->user();
    return $user;
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/calendar/data', [CalendarController::class, 'getCalendarData']);
    Route::get('/candidates/{candidate}', [\App\Http\Controllers\CandidateController::class, 'showApi']);
    Route::get('/departments/{department}/positions', [DepartmentController::class, 'positions'])->name('api.departments.positions');
});
