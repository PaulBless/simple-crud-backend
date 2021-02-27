<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Running',
        'payload' => null,
        'status'  => Constants::STATUS_CODE_SUCCESS
    ]);
});

//log viewer
Route::get('system-logs', ['\Rap2hpoutre\LaravelLogViewer\LogViewerController', 'index']);