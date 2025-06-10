<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Mail\TokenEmail;
use Carbon\Carbon;

class OAuthController extends Controller
{
    private $authUrl = 'https://web.passolution.eu/en/oauth/authorize';
    private $tokenUrl = 'https://web.passolution.eu/en/oauth/token';
    private $logoutUrl = 'https://web.passolution.eu/en/oauth/logout';
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
            'state' => $state,
            'prompt' => 'login', // Erzwingt neuen Login
            'max_age' => '0' // Maximales Alter der Authentifizierung in Sekunden
        ];

        // Optional: Auch consent erneut anfordern
        if ($request->has('force_consent')) {
            $params['prompt'] = 'login consent';
        }

        $authorizationUrl = $this->authUrl . '?' . http_build_query($params);

        Log::info('OAuth authorization started', [
            'client_id' => $clientId,
            'state' => $state,
            'prompt' => $params['prompt']
        ]);

        return redirect($authorizationUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');

        if ($error) {
            Log::error('OAuth callback error', ['error' => $error]);
            return view('oauth.error', ['error' => $error]);
        }

        if (!$code || $state !== session('oauth_state')) {
            Log::error('OAuth callback validation failed', [
                'code_present' => !empty($code),
                'state_match' => $state === session('oauth_state')
            ]);
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
                'success' => $response->successful()
            ]);

            if ($response->successful()) {
                $tokenData = $response->json();
                
                // Clear session data after successful token exchange
                session()->forget(['oauth_client_id', 'oauth_client_secret']);
                
                Log::info('Token exchange successful', [
                    'client_id' => $clientId,
                    'has_refresh_token' => isset($tokenData['refresh_token'])
                ]);
                
                return view('oauth.tokens', [
                    'tokenData' => $tokenData,
                    'clientId' => $clientId,
                    'timestamp' => Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s')
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
            
            Log::info('Token email sent successfully', [
                'email' => $email,
                'client_id' => $clientId
            ]);
            
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

    public function logout(Request $request)
    {
        // Session-Daten zuerst lokal löschen
        session()->forget(['oauth_client_id', 'oauth_client_secret', 'oauth_state']);
        session()->flush(); // Komplette Session löschen
        
        // Redirect URI nach dem Logout - mit speziellem Post-Logout Handler
        $redirectAfterLogout = route('oauth.post-logout');
        
        // Logout-Parameter für OpenID Connect End Session
        $logoutParams = [
            'post_logout_redirect_uri' => $redirectAfterLogout,
        ];
        
        // Wenn Client ID verfügbar ist, mitgeben
        $clientId = $request->get('client_id') ?: session('oauth_client_id');
        if ($clientId) {
            $logoutParams['client_id'] = $clientId;
        }
        
        $fullLogoutUrl = $this->logoutUrl . '?' . http_build_query($logoutParams);
        
        Log::info('User logout initiated', [
            'logout_url' => $fullLogoutUrl,
            'redirect_uri' => $redirectAfterLogout,
            'client_id' => $clientId
        ]);
        
        return redirect($fullLogoutUrl);
    }

    public function forceLogout()
    {
        // Session-Daten löschen
        session()->forget(['oauth_client_id', 'oauth_client_secret', 'oauth_state']);
        session()->flush();
        
        Log::info('Force logout executed');
        
        return redirect()->route('oauth.index')->with('success', 'Session wurde zurückgesetzt. Starten Sie einen neuen OAuth2-Flow für eine neue Autorisierung.');
    }

    public function postLogout(Request $request)
    {
        // Diese Route wird nach dem Passolution-Logout aufgerufen
        session()->flush(); // Sicherheitshalber nochmal Session löschen
        
        Log::info('Post-logout callback received', [
            'params' => $request->all()
        ]);
        
        return redirect()->route('oauth.index')->with('success', 'Sie wurden erfolgreich von Passolution abgemeldet. Der nächste OAuth2-Flow erfordert eine neue Anmeldung.');
    }

    public function authorizeWithForceConsent(Request $request)
    {
        // Spezielle Route für erzwungene Re-Autorisierung mit Consent
        $request->merge(['force_consent' => true]);
        return $this->authorize($request);
    }
}