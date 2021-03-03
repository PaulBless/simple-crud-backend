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

Route::get('/artisan/{cmd}', function ($cmd) {
    if (env('APP_ENV') !== 'production') {
        try {
            Artisan::call($cmd);

            return response()->json([
                'message' => 'Command successfully completed',
                'payload' => null,
                'status'  => Constants::STATUS_CODE_SUCCESS
            ]); 
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
});

//log viewer
Route::get('system-logs', ['\Rap2hpoutre\LaravelLogViewer\LogViewerController', 'index']);