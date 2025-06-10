<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Mail\TokenEmail;

class OAuthController extends Controller
{
    private $authUrl = 'https://web.passolution.eu/en/oauth/authorize';
    private $tokenUrl = 'https://web.passolution.eu/en/oauth/token';
    private $redirectUri = 'https://api-client-oauth2-v3.passolution.de/oauth/callback';
    private $apiBaseUrl = 'https://api.passolution.eu/api/v2';

    public function index()
    {
        return view('oauth.index');
    }

    public function authorize(Request $request)
    {
        $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');
        
        // Store in session for later use
        session([
            'oauth_client_id' => $clientId,
            'oauth_client_secret' => $clientSecret
        ]);

        // Generate new state for each authorization request
        $state = bin2hex(random_bytes(16));
        session(['oauth_state' => $state]);

        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => '',
            'state' => $state
        ];

        $authorizationUrl = $this->authUrl . '?' . http_build_query($params);

        return redirect($authorizationUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');

        if ($error) {
            return view('oauth.error', ['error' => $error]);
        }

        if (!$code || $state !== session('oauth_state')) {
            return view('oauth.error', ['error' => 'Invalid state or missing authorization code']);
        }

        $clientId = session('oauth_client_id');
        $clientSecret = session('oauth_client_secret');

        if (!$clientId || !$clientSecret) {
            return view('oauth.error', ['error' => 'Client credentials not found in session']);
        }

        // Clear the state after use to prevent reuse
        session()->forget('oauth_state');

        try {
            Log::info('Attempting token exchange', [
                'client_id' => $clientId,
                'code_length' => strlen($code),
                'redirect_uri' => $this->redirectUri
            ]);

            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ]);

            Log::info('Token response received', [
                'status' => $response->status(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                
                // Clear session data after successful token exchange
                session()->forget(['oauth_client_id', 'oauth_client_secret']);
                
                return view('oauth.tokens', [
                    'tokenData' => $tokenData,
                    'clientId' => $clientId,
                    'timestamp' => now()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s')
                ]);
            } else {
                $responseBody = $response->body();
                $statusCode = $response->status();
                
                Log::error('Token exchange failed', [
                    'status' => $statusCode,
                    'response' => $responseBody,
                    'client_id' => $clientId
                ]);
                
                // Parse error response if it's JSON
                $errorData = json_decode($responseBody, true);
                $errorMessage = $errorData['error_description'] ?? 'Failed to exchange authorization code for tokens';
                
                return view('oauth.error', [
                    'error' => $errorMessage,
                    'details' => "HTTP {$statusCode}: {$responseBody}"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during token exchange', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('oauth.error', ['error' => 'An error occurred during token exchange: ' . $e->getMessage()]);
        }
    }

    public function sendTokens(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token_data' => 'required|string',
            'client_id' => 'required|string',
            'timestamp' => 'required|string'
        ]);

        $tokenData = json_decode($request->input('token_data'), true);
        $email = $request->input('email');
        $clientId = $request->input('client_id');
        $timestamp = $request->input('timestamp');

        try {
            Mail::to($email)->send(new TokenEmail($tokenData, $clientId, $timestamp));
            
            return response()->json([
                'success' => true,
                'message' => 'Tokens successfully sent to ' . $email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send token email', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $request->input('refresh_token'),
                'client_id' => $request->input('client_id'),
                'client_secret' => $request->input('client_secret'),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to refresh token',
                    'error' => $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}