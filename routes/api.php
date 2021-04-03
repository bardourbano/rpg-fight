<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\FightController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/users', [UserController::class, 'store'])->name('users.store');

Route::middleware('basic.auth:api')->group(function () {
    Route::get('/users/{user}/fights', [FightController::class, 'index'])->name('users.fights.index');
    Route::post('/fights', [FightController::class, 'store'])->name('fights.store');
    Route::patch('/fights/{fight}', [FightController::class, 'update'])->name('fights.update');

    Route::get('/heroes', [CharacterController::class, 'index'])->name('heroes.index');

    Route::get('/ranking', [UserController::class, 'index'])->name('users.ranking');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
});
