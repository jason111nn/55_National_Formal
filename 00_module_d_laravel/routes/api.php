<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 定義 /books.json 查詢入口，指向 BookController@indexApi (競賽要求)
Route::get('/books.json', [\App\Http\Controllers\BookController::class, 'indexApi'])
    ->name('api.books');
