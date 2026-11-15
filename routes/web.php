<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:supervisor'])->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::post('/items/{item}', [ItemController::class, 'update']);
});

Route::middleware(['auth', 'role:member'])->group(function () {
    Route::get('/bookings/create', [BookingController::class, 'create']);
    Route::post('/bookings', [BookingController::class, 'store']);
});
