<?php

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


Route::group(['prefix' => 'v1'], function () {
    Route::get('/status', function () {
        return response()->json([
            'message' => 'Running',
            'payload' => null,
            'status'  => Constants::STATUS_CODE_SUCCESS
        ]);
    });

    //auth
    Route::group(['middleware' => ['throttle:30,1']], function () { //max 30 request in i min
        Route::post('/login', ['App\Http\Controllers\Api\AuthController', 'login']);
        Route::post('/registration', ['App\Http\Controllers\Api\AuthController', 'registration']);
        Route::post('/forget-password', ['App\Http\Controllers\Api\AuthController', 'forgetPassword']);
        Route::post('/reset-password', ['App\Http\Controllers\Api\AuthController', 'resetPassword']);
    });
});
