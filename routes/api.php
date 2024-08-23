<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Bridge\OutBoundController;
use App\Http\Controllers\Api\UaribaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class,'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/register', [AuthController::class,'register'])->name('register');
    Route::post('/bridge/outbound', OutBoundController::class)->name('outbound');
    Route::post('/bridge/inbound', OutBoundController::class)->name('bridge.inbound');

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::match(['post','get'],'/uariba',[UaribaController::class,'index'])->name('uariba')->withoutMiddleware([
    'auth'
]);
