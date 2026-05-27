<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas protegidas...
Route::middleware(['auth:sanctum', 'pass'])->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function (){
        Route::put('active-user/{identity}', 'activeUser');
        Route::put('desactive-user/{identity}', 'desactiveUser');
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/', [AuthController::class, 'detailsUser']);
        Route::get('/{identity}', [AuthController::class, 'getUserById']);
    });
});

//Rutas desprotegidas...
Route::prefix('app')->controller(AuthController::class)->group(function (){
    Route::post('/', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::put('validate-email', 'validateEmail');//
    Route::post('validate-user', 'validateUser');//
    Route::put('change-password', 'changePassword');//
})->withoutMiddleware(['auth:sanctum']);   
