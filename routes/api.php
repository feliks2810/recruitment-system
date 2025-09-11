<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/calendar/data', [CalendarController::class, 'getCalendarData']);
    Route::get('/candidates/{candidate}', [\App\Http\Controllers\CandidateController::class, 'showApi']);
});
