<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\EntityCreateController;

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

Route::get('/auth', [AuthController::class, 'authUser'])->name(('auth.user'));
Route::get('/auth-callback', [AuthController::class, 'authCallback']);

Route::get('/test', [TestController::class, 'test']);

Route::get('/entity/create', [EntityCreateController::class, 'create'])->name('entity.create');
Route::post('/entity', [EntityCreateController::class, 'store'])->name('entity.store');
