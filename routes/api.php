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

Route::post('/users', [UserController::class, 'store']);

Route::middleware('basic.auth:api')->group(function () {
    Route::post('/fights', [FightController::class, 'store']);
    Route::patch('/fights/{fight}', [FightController::class, 'update']);

    Route::get('/heroes', [CharacterController::class, 'index']);

    Route::get('/ranking', [UserController::class, 'index']);
});
