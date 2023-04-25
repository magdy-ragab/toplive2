<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/*
GET /users index users.index
PUT /users store users.store
PUT /users/update update users.update
GET /users/{id} show users.show
PUT/PATCH /users/{id} update users.update
DELETE /users/{id} destroy users.destroy
 */

Route::get('/users', [UserController::class, 'index']);
Route::get('/show/{id}', [UserController::class, 'show']);
