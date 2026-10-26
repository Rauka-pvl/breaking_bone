<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'screen']);
Route::post('/', [GameController::class, 'hostLogin'])->middleware('throttle:10,1');
Route::post('/logout', [GameController::class, 'hostLogout']);
Route::get('/play/{room:code}/{side}', [GameController::class, 'play']);

Route::prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [AdminController::class, 'logout']);
    Route::post('/keys', [AdminController::class, 'store']);
    Route::patch('/keys/{hostKey}/toggle', [AdminController::class, 'toggle']);
    Route::delete('/keys/{hostKey}', [AdminController::class, 'destroy']);
});
