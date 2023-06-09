<?php

use App\Http\Controllers\StreamController;
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


Route::get ( '/users/test'            , [ UserController::class , 'test'             ] );
Route::get ( '/users'                 , [ UserController::class , 'index'            ] );
Route::get ( '/users/show/{id}'       , [ UserController::class , 'show'             ] );

Route::post( '/users/storeByPhone'    , [ UserController::class , 'storeByPhone'     ] );
Route::post( '/users/loginByFacebook' , [ UserController::class , 'loginByFacebook'  ] );
Route::post( '/users/loginByGoogle'   , [ UserController::class , 'loginByGoogle'    ] );
Route::post( '/users/loginByEmail'    , [ UserController::class , 'loginEmail'       ] );
Route::post( '/users/loginByMobile'   , [ UserController::class , 'loginByMobile'    ] );
Route::post( '/users/updateUserData'  , [ UserController::class , 'updateUserData'   ] );
Route::post( '/users/updateUserImage' , [ UserController::class , 'updateUserImage'  ] );

Route::post( '/users/current'         , [ UserController::class , 'getCurrentUser'   ] );

Route::post( '/agoraToken'            , [ StreamController::class , 'agoraToken'     ] );
Route::post( '/createAgoraRoom'       , [ StreamController::class , 'createRoom'     ] );

// Route::post('/users/veifyOtp', [UserController::class,'verifyOtp']);
// Route::post('/users/newOtp', [UserController::class,'newOtp']);







