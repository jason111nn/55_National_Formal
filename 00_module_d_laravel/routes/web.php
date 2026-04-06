<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// 公開展示頁面路由： /01/{isbn} (競賽要求)
// 允許參數中含有 '-' 號
Route::get('/01/{isbn}', [\App\Http\Controllers\BookController::class, 'showPublic'])
    ->where('isbn', '.*')
    ->name('book.public');
