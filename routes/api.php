<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::prefix('rooms/{room:code}')->group(function () {
    Route::get('/state', [GameController::class, 'state']);
    Route::post('/join', [GameController::class, 'join']);
    Route::post('/leave', [GameController::class, 'leave']);
    Route::post('/taps', [GameController::class, 'taps']);
    Route::post('/finish', [GameController::class, 'finish']);
    Route::post('/start', [GameController::class, 'start']);
    Route::post('/reset', [GameController::class, 'reset']);
});
