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
Route::get('/test', [TestController::class, 'test']);
Route::get('/test2', [TestController::class, 'test2']);

Route::get('/test3', [TestController::class, 'test3']);

Route::get('/auth-callback', [AuthController::class, 'authCallback']);


Route::get('/create', [EntityCreateController::class, 'createEntity']);
Route::get('/entiry/create', [EntityCreateController::class, 'create'])->name('entiry.create');
Route::post('/entiry', [EntityCreateController::class, 'store'])->name('entiry.store');
