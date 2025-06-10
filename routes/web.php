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

// Test-Route für Debugging
Route::get('/test', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now(),
        'timezone' => config('app.timezone'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version()
    ]);
});

// OAuth2 Routes
Route::get('/', [OAuthController::class, 'index'])->name('oauth.index');
Route::post('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
Route::post('/oauth/authorize-force', [OAuthController::class, 'authorizeWithForceConsent'])->name('oauth.authorize-force');
Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
Route::post('/oauth/send-tokens', [OAuthController::class, 'sendTokens'])->name('oauth.send-tokens');
Route::post('/oauth/refresh-token', [OAuthController::class, 'refreshToken'])->name('oauth.refresh-token');
Route::get('/oauth/logout', [OAuthController::class, 'logout'])->name('oauth.logout');
Route::get('/oauth/force-logout', [OAuthController::class, 'forceLogout'])->name('oauth.force-logout');
Route::get('/oauth/post-logout', [OAuthController::class, 'postLogout'])->name('oauth.post-logout');