<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;

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

Route::get('/', [OAuthController::class, 'index'])->name('oauth.index');
Route::post('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::post('/oauth/send-tokens', [OAuthController::class, 'sendTokens'])->name('oauth.send-tokens');