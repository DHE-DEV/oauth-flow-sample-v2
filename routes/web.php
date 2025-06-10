<?php

use App\Http\Controllers\OAuth2FlowController;
use Illuminate\Support\Facades\Route;

// OAuth2 Flow Visualizer Routes
Route::get('/', [OAuth2FlowController::class, 'index'])->name('oauth2.index');

Route::prefix('oauth2')->name('oauth2.')->group(function () {
    // API Routes for OAuth2 Flow
    Route::post('/generate-auth-url', [OAuth2FlowController::class, 'generateAuthUrl'])->name('generate-auth-url');
    Route::post('/handle-callback', [OAuth2FlowController::class, 'handleCallback'])->name('handle-callback');
    Route::post('/exchange-tokens', [OAuth2FlowController::class, 'exchangeCodeForTokens'])->name('exchange-tokens');
    Route::post('/refresh-token', [OAuth2FlowController::class, 'refreshToken'])->name('refresh-token');
    Route::post('/test-api', [OAuth2FlowController::class, 'testApiCall'])->name('test-api');
    Route::post('/send-email', [OAuth2FlowController::class, 'sendTokensEmail'])->name('send-email');
    Route::get('/flow-data', [OAuth2FlowController::class, 'getFlowData'])->name('flow-data');
    Route::post('/reset', [OAuth2FlowController::class, 'resetFlow'])->name('reset');
});

// Fallback route
Route::fallback(function () {
    return redirect()->route('oauth2.index');
});