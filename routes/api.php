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
        Route::post('/login', ['App\Http\Controllers\Api\UserController', 'login']);
        Route::post('/signup', ['App\Http\Controllers\Api\UserController', 'signup']);
        Route::post('/forget-password', ['App\Http\Controllers\Api\UserController', 'forgetPassword']);
        Route::post('/reset-password', ['App\Http\Controllers\Api\UserController', 'resetPassword']);
    });

    Route::group(['middleware' => ['jwt.verify']], function () {
        Route::post('/refresh-token', ['App\Http\Controllers\Api\UserController', 'refreshToken'])->name('refresh-token');
        Route::get('/me', ['App\Http\Controllers\Api\UserController', 'me']);

        Route::get('/products', ['App\Http\Controllers\Api\ProductController', 'products']);
        Route::get('/product', ['App\Http\Controllers\Api\ProductController', 'product']);
        Route::post('/product', ['App\Http\Controllers\Api\ProductController', 'product']);
        Route::delete('/product', ['App\Http\Controllers\Api\ProductController', 'product']);
    });
});
