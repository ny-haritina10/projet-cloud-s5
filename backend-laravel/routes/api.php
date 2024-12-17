<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SignupController;
use App\Http\Controllers\Api\SignupSMTPController;
use App\Http\Controllers\Api\UserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();    
});

Route::prefix('auth')->group(function () {
    Route::post('/smtp/signup', [SignupSMTPController::class, 'signup']);
    Route::post('/smtp/verify-email', [SignupSMTPController::class, 'verifyEmail']);
});

Route::prefix('users')->group(function () {
    Route::put('/{id}', [UserController::class, 'update']);
});