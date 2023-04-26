<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/users/get_csrf', [User::class, 'get_csrf']);
Route::get('/users/current', [UserController::class, 'getCurrentUser']);

Route::put('/users/store', [UserController::class, 'store']);
