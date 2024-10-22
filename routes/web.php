<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DietController;


Route::get('/', [DietController::class, 'index'])->name('diet.index');

Route::get('/back', [DietController::class, 'back'])->name('diet.back');

Route::post('/calculate', [DietController::class, 'calculate'])->name('diet.calculate');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

