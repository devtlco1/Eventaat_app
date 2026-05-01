<?php

use App\Http\Controllers\DashboardEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [DashboardEntryController::class, 'show'])->name('dashboard.login');
Route::post('/login', [DashboardEntryController::class, 'authenticate'])->name('dashboard.login.authenticate');
