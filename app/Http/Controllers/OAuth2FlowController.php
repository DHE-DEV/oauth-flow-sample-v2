<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\OAuth2TokenMail;

class OAuth2FlowController extends Controller
{
    /**
     * Display the OAuth2 flow visualization page
     */
    public function index()
    {
        return view('oauth2.flow');
    }

    /**
     * Step 1: Generate authorization URL (OHNE PKCE für Passolution)
     */
    public function generateAuthUrl(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'client_id' => 'required|string',
                'redirect_uri' => 'required|url',
                'authorization_endpoint' => 'required|url',
            ]);

            $state = Str::random(32);

            // EINFACHE Authorization URL ohne PKCE
            $params = [
                'response_type' => 'code',
                'client_id' => $request->client_id,
                'redirect_uri' => $request->redirect_uri,
                'state' => $state,
            ];

            // Nur Scope hinzufügen wenn angegeben
            if (!empty($request->scope)) {
                $params['scope'] = $request->scope;
            }

            $authUrl = $request->authorization_endpoint . '?' . http_build_query($params);

            // Store in session for this flow
            session([
                'oauth2_flow' => [
                    'client_id' => $request->client_id,
                    'client_secret' => $request->client_secret,
                    'redirect_uri' => $request->redirect_uri,
                    'token_endpoint' => $request->token_endpoint,
                    'state' => $state,
                    'scope' => $request->scope ?? '',
                ]
            ]);

            Log::info('OAuth2: Simple auth URL generated (no PKCE)', [
                'client_id' => $request->client_id,
                'state' => $state,
                'scope' => $request->scope ?? 'none'
            ]);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'state' => $state,
                'step' => 1,
                'message' => 'Authorization URL erfolgreich generiert (ohne PKCE)'
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Auth URL generation failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler: ' . $e->getMessage(),
                'step' => 1
            ], 500);
        }
    }

    /**
     * Step 2: Handle authorization callback
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'authorization_code' => 'required|string',
                'state' => 'required|string',
            ]);

            $flowData = session('oauth2_flow');
            
            if (!$flowData) {
                Log::error('OAuth2: No session data found');
                return response()->json([
                    'success' => false,
                    'error' => 'Keine Session-Daten gefunden. Starten Sie den Flow neu.',
                    'step' => 2
                ], 400);
            }

            if ($flowData['state'] !== $request->state) {
                Log::warning('OAuth2: State mismatch', [
                    'expected' => $flowData['state'],
                    'received' => $request->state
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'State-Parameter stimmt nicht überein',
                    'step' => 2
                ], 400);
            }

            // Store the authorization code
            session(['oauth2_flow.authorization_code' => $request->authorization_code]);

            Log::info('OAuth2: Authorization code stored', [
                'code_length' => strlen($request->authorization_code),
                'code_preview' => substr($request->authorization_code, 0, 10) . '...'
            ]);

            return response()->json([
                'success' => true,
                'authorization_code' => $request->authorization_code,
                'state_verified' => true,
                'step' => 2,
                'message' => 'Authorization Code empfangen'
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Callback failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'step' => 2
            ], 500);
        }
    }

    /**
     * Step 3: Exchange authorization code for tokens (VEREINFACHT)
     */
    public function exchangeCodeForTokens(Request $request): JsonResponse
    {
        try {
            $flowData = session('oauth2_flow');
            
            // Session-Validierung
            if (!$flowData) {
                Log::error('OAuth2: No flow data in session');
                return response()->json([
                    'success' => false,
                    'error' => 'Keine Flow-Daten in Session. Bitte Flow neu starten.',
                    'step' => 3
                ], 400);
            }

            if (!isset($flowData['authorization_code'])) {
                Log::error('OAuth2: No authorization code in session');
                return response()->json([
                    'success' => false,
                    'error' => 'Kein Authorization Code in Session. Bitte Schritt 2 wiederholen.',
                    'step' => 3
                ], 400);
            }

            // Erforderliche Felder prüfen
            $requiredFields = ['client_id', 'client_secret', 'authorization_code', 'redirect_uri', 'token_endpoint'];
            foreach ($requiredFields as $field) {
                if (empty($flowData[$field])) {
                    Log::error('OAuth2: Missing required field', ['field' => $field]);
                    return response()->json([
                        'success' => false,
                        'error' => "Fehlendes Feld: {$field}",
                        'step' => 3
                    ], 400);
                }
            }

            // Simulation oder echte API
            if ($request->has('simulate') && $request->simulate) {
                Log::info('OAuth2: Using simulation mode');
                $tokens = $this->simulateTokenResponse();
            } else {
                Log::info('OAuth2: Making real token exchange');
                
                // Erste Versuch: Basic Authentication (Standard)
                $tokenData = [
                    'grant_type' => 'authorization_code',
                    'code' => $flowData['authorization_code'],
                    'redirect_uri' => $flowData['redirect_uri'],
                ];

                $basicAuth = base64_encode($flowData['client_id'] . ':' . $flowData['client_secret']);

                Log::info('OAuth2: Trying Basic Auth first', [
                    'endpoint' => $flowData['token_endpoint'],
                    'client_id' => $flowData['client_id'],
                    'redirect_uri' => $flowData['redirect_uri'],
                    'auth_method' => 'Basic Authentication'
                ]);

                $response = Http::asForm()
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'OAuth2-Flow-Visualizer/1.0',
                        'Authorization' => 'Basic ' . $basicAuth
                    ])
                    ->timeout(30)
                    ->post($flowData['token_endpoint'], $tokenData);

                // Falls Basic Auth fehlschlägt, versuche Body-Parameter
                if (!$response->successful() && $response->status() === 401) {
                    Log::info('OAuth2: Basic Auth failed, trying client credentials in body');
                    
                    $tokenDataWithCredentials = [
                        'grant_type' => 'authorization_code',
                        'client_id' => $flowData['client_id'],
                        'client_secret' => $flowData['client_secret'],
                        'code' => $flowData['authorization_code'],
                        'redirect_uri' => $flowData['redirect_uri'],
                    ];

                    $response = Http::asForm()
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'User-Agent' => 'OAuth2-Flow-Visualizer/1.0'
                        ])
                        ->timeout(30)
                        ->post($flowData['token_endpoint'], $tokenDataWithCredentials);
                }

                Log::info('OAuth2: Token response received', [
                    'status' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'body_length' => strlen($response->body()),
                    'body_preview' => substr($response->body(), 0, 200)
                ]);

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    $errorData = null;
                    
                    try {
                        $errorData = $response->json();
                    } catch (\Exception $e) {
                        Log::warning('OAuth2: Could not parse error response as JSON');
                    }
                    
                    Log::error('OAuth2: Token exchange failed', [
                        'status' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $errorBody,
                        'parsed_error' => $errorData
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Token-Austausch fehlgeschlagen: ' . 
                                  ($errorData['error_description'] ?? 
                                   $errorData['message'] ?? 
                                   "HTTP {$response->status()}: {$errorBody}"),
                        'step' => 3,
                        'debug_info' => [
                            'status' => $response->status(),
                            'endpoint' => $flowData['token_endpoint'],
                            'response_preview' => substr($errorBody, 0, 500)
                        ]
                    ], 400);
                }

                $tokens = $response->json();
                
                if (!$tokens || !isset($tokens['access_token'])) {
                    Log::error('OAuth2: Invalid token response', ['response' => $tokens]);
                    return response()->json([
                        'success' => false,
                        'error' => 'Ungültige Token-Antwort erhalten',
                        'step' => 3
                    ], 400);
                }

                Log::info('OAuth2: Tokens received successfully', [
                    'token_type' => $tokens['token_type'] ?? 'unknown',
                    'has_access_token' => !empty($tokens['access_token']),
                    'has_refresh_token' => !empty($tokens['refresh_token']),
                    'expires_in' => $tokens['expires_in'] ?? 'not_specified'
                ]);
            }

            // Store tokens in session
            session(['oauth2_flow.tokens' => $tokens]);

            return response()->json([
                'success' => true,
                'tokens' => $tokens,
                'step' => 3,
                'message' => 'Tokens erfolgreich erhalten'
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Token exchange exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unerwarteter Fehler: ' . $e->getMessage(),
                'step' => 3
            ], 500);
        }
    }

    /**
     * Weitere Methoden (refreshToken, sendTokensEmail, etc.)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        // Implementierung wie vorher...
        return response()->json(['success' => false, 'error' => 'Not implemented yet']);
    }

    public function testApiCall(Request $request): JsonResponse
    {
        // Implementierung wie vorher...
        return response()->json(['success' => false, 'error' => 'Not implemented yet']);
    }

    public function sendTokensEmail(Request $request): JsonResponse
    {
        // Implementierung wie vorher...
        return response()->json(['success' => false, 'error' => 'Not implemented yet']);
    }

    public function getFlowData(): JsonResponse
    {
        $flowData = session('oauth2_flow', []);
        if (isset($flowData['client_secret'])) {
            $flowData['client_secret'] = '***HIDDEN***';
        }
        return response()->json($flowData);
    }

    public function resetFlow(): JsonResponse
    {
        session()->forget('oauth2_flow');
        return response()->json([
            'success' => true,
            'message' => 'OAuth2 Flow zurückgesetzt'
        ]);
    }

    /**
     * Simulate token response for demo purposes
     */
    private function simulateTokenResponse(): array
    {
        return [
            'access_token' => 'demo_access_token_' . Str::random(32),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'demo_refresh_token_' . Str::random(32),
            'scope' => 'default'
        ];
    }
}