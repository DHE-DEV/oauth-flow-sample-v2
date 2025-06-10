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
        // Passolution-Konfiguration aus Config laden
        $passolutionConfig = config('oauth2.default_providers.passolution');
        
        return view('oauth2.flow', [
            'passolution_config' => $passolutionConfig,
            'demo_mode' => config('oauth2.demo_mode', true)
        ]);
    }

    /**
     * Step 1: Generate authorization URL
     */
    public function generateAuthUrl(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'client_id' => 'required|string',
                'redirect_uri' => 'required|url',
                'authorization_endpoint' => 'required|url',
                'scope' => 'nullable|string',
            ]);

            $state = Str::random(32);
            $codeVerifier = Str::random(128);
            $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

            $params = [
                'response_type' => 'code',
                'client_id' => $request->client_id,
                'redirect_uri' => $request->redirect_uri,
                'state' => $state,
                // Temporär PKCE deaktivieren für Passolution Test
                // 'code_challenge' => $codeChallenge,
                // 'code_challenge_method' => 'S256',
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
                    'authorization_endpoint' => $request->authorization_endpoint,
                    'state' => $state,
                    'code_verifier' => $codeVerifier,
                    'code_challenge' => $codeChallenge,
                    'scope' => $request->scope ?? '',
                    'provider' => $this->detectProvider($request->authorization_endpoint),
                ]
            ]);

            Log::info('OAuth2: Authorization URL generated', [
                'client_id' => $request->client_id,
                'provider' => $this->detectProvider($request->authorization_endpoint),
                'scope' => $request->scope ?? 'none'
            ]);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'state' => $state,
                'code_verifier' => $codeVerifier,
                'code_challenge' => $codeChallenge,
                'step' => 1,
                'message' => 'Authorization URL erfolgreich generiert',
                'provider' => $this->detectProvider($request->authorization_endpoint)
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Authorization URL generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Generieren der Authorization URL: ' . $e->getMessage(),
                'step' => 1
            ], 500);
        }
    }

    /**
     * Step 2: Handle authorization callback simulation
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'authorization_code' => 'required|string',
                'state' => 'required|string',
            ]);

            $flowData = session('oauth2_flow');
            
            Log::info('OAuth2: Handling callback', [
                'has_flow_data' => !empty($flowData),
                'provided_state' => $request->state,
                'session_state' => $flowData['state'] ?? 'missing'
            ]);

            if (!$flowData || $flowData['state'] !== $request->state) {
                Log::warning('OAuth2: State parameter validation failed');
                
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid state parameter - Security check failed',
                    'step' => 2
                ], 400);
            }

            // Store the authorization code
            session(['oauth2_flow.authorization_code' => $request->authorization_code]);

            Log::info('OAuth2: Authorization code stored successfully');

            return response()->json([
                'success' => true,
                'authorization_code' => $request->authorization_code,
                'state_verified' => true,
                'step' => 2,
                'message' => 'Authorization Code empfangen und State-Parameter verifiziert'
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Callback handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Verarbeiten des Callbacks: ' . $e->getMessage(),
                'step' => 2
            ], 500);
        }
    }

    /**
     * Step 3: Exchange authorization code for tokens
     */
    public function exchangeCodeForTokens(Request $request): JsonResponse
    {
        try {
            $flowData = session('oauth2_flow');
            
            Log::info('OAuth2: Starting token exchange', [
                'has_flow_data' => !empty($flowData),
                'has_auth_code' => !empty($flowData['authorization_code'] ?? null),
                'simulate' => $request->simulate ?? false
            ]);

            if (!$flowData) {
                Log::error('OAuth2: No flow data in session');
                return response()->json([
                    'success' => false,
                    'error' => 'Keine OAuth2 Flow-Daten in der Session gefunden',
                    'step' => 3
                ], 400);
            }

            if (!isset($flowData['authorization_code'])) {
                Log::error('OAuth2: No authorization code in session');
                return response()->json([
                    'success' => false,
                    'error' => 'Kein Authorization Code in der Session gefunden',
                    'step' => 3
                ], 400);
            }

            // Simulate token exchange (in real scenario, this would be a real API call)
            if ($request->has('simulate') && $request->simulate) {
                Log::info('OAuth2: Using simulation mode');
                
                // Simulate successful token exchange
                $tokens = $this->simulateTokenResponse($flowData['provider'] ?? 'passolution');
            } else {
                Log::info('OAuth2: Making real API call to token endpoint');
                
                // Make real API call to token endpoint
                $tokenData = [
                    'grant_type' => 'authorization_code',
                    'client_id' => $flowData['client_id'],
                    'client_secret' => $flowData['client_secret'],
                    'code' => $flowData['authorization_code'],
                    'redirect_uri' => $flowData['redirect_uri'],
                ];

                // Nur code_verifier hinzufügen wenn PKCE verwendet wird
                if (!empty($flowData['code_verifier'])) {
                    $tokenData['code_verifier'] = $flowData['code_verifier'];
                }

                Log::info('OAuth2: Token request data', [
                    'endpoint' => $flowData['token_endpoint'],
                    'grant_type' => $tokenData['grant_type'],
                    'client_id' => $tokenData['client_id'],
                    'redirect_uri' => $tokenData['redirect_uri'],
                    'has_code' => !empty($tokenData['code']),
                    'code_length' => strlen($tokenData['code']),
                    'has_code_verifier' => !empty($tokenData['code_verifier']),
                    'has_client_secret' => !empty($tokenData['client_secret']),
                    'all_params' => array_keys($tokenData)
                ]);

                // Spezielle Header für Passolution
                $headers = [
                    'Accept' => 'application/json',
                    'User-Agent' => 'OAuth2-Flow-Visualizer/1.0',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ];

                // Für Passolution: Möglicherweise Basic Auth statt client_secret im Body
                if (str_contains($flowData['token_endpoint'], 'passolution')) {
                    // Versuche beide Methoden - zuerst mit client_secret im Body
                    Log::info('OAuth2: Using Passolution-specific configuration');
                } else {
                    // Für andere Provider könnte Basic Auth erforderlich sein
                    $headers['Authorization'] = 'Basic ' . base64_encode($flowData['client_id'] . ':' . $flowData['client_secret']);
                    unset($tokenData['client_secret']);
                }

                $response = Http::asForm()
                    ->withHeaders($headers)
                    ->timeout(30)
                    ->post($flowData['token_endpoint'], $tokenData);

                Log::info('OAuth2: Token endpoint response', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body_preview' => substr($response->body(), 0, 200)
                ]);

                if (!$response->successful()) {
                    $errorBody = $response->json();
                    
                    Log::error('OAuth2: Token exchange failed', [
                        'status' => $response->status(),
                        'error_body' => $errorBody,
                        'full_response' => $response->body()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Token exchange failed: ' . ($errorBody['error_description'] ?? $errorBody['message'] ?? $response->body()),
                        'step' => 3,
                        'http_status' => $response->status(),
                        'debug_info' => [
                            'endpoint' => $flowData['token_endpoint'],
                            'response_body' => $response->body()
                        ]
                    ], 400);
                }

                $tokens = $response->json();
                
                Log::info('OAuth2: Tokens received successfully', [
                    'token_type' => $tokens['token_type'] ?? 'unknown',
                    'has_access_token' => !empty($tokens['access_token']),
                    'has_refresh_token' => !empty($tokens['refresh_token']),
                    'expires_in' => $tokens['expires_in'] ?? 'not_specified'
                ]);
            }

            // Store tokens in session (temporary)
            session(['oauth2_flow.tokens' => $tokens]);

            return response()->json([
                'success' => true,
                'tokens' => $tokens,
                'step' => 3,
                'message' => 'Tokens erfolgreich erhalten',
                'provider' => $flowData['provider'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Token exchange exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Token exchange failed: ' . $e->getMessage(),
                'step' => 3
            ], 500);
        }
    }

    /**
     * Step 4: Refresh access token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $flowData = session('oauth2_flow');
            
            if (!$flowData || !isset($flowData['tokens']['refresh_token'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kein Refresh Token verfügbar',
                    'step' => 4
                ], 400);
            }

            if ($request->has('simulate') && $request->simulate) {
                // Simulate refresh token response
                $newTokens = $this->simulateRefreshTokenResponse($flowData['provider'] ?? 'passolution');
            } else {
                // Make real API call to refresh token
                $refreshData = [
                    'grant_type' => 'refresh_token',
                    'client_id' => $flowData['client_id'],
                    'client_secret' => $flowData['client_secret'],
                    'refresh_token' => $flowData['tokens']['refresh_token'],
                ];

                // Spezielle Header für Passolution
                $headers = [
                    'Accept' => 'application/json',
                    'User-Agent' => 'OAuth2-Flow-Visualizer/1.0'
                ];

                if (str_contains($flowData['token_endpoint'], 'passolution')) {
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                }

                $response = Http::asForm()
                    ->withHeaders($headers)
                    ->timeout(30)
                    ->post($flowData['token_endpoint'], $refreshData);

                if (!$response->successful()) {
                    $errorBody = $response->json();
                    return response()->json([
                        'success' => false,
                        'error' => 'Token refresh failed: ' . ($errorBody['error_description'] ?? $response->body()),
                        'step' => 4
                    ], 400);
                }

                $newTokens = $response->json();
            }

            // Update tokens in session
            session(['oauth2_flow.tokens' => array_merge($flowData['tokens'], $newTokens)]);

            return response()->json([
                'success' => true,
                'tokens' => $newTokens,
                'step' => 4,
                'message' => 'Token erfolgreich erneuert'
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth2: Token refresh failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Token refresh failed: ' . $e->getMessage(),
                'step' => 4
            ], 500);
        }
    }

    /**
     * Test API call with obtained token
     */
    public function testApiCall(Request $request): JsonResponse
    {
        try {
            $flowData = session('oauth2_flow');
            
            if (!$flowData || !isset($flowData['tokens']['access_token'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kein Access Token verfügbar'
                ], 400);
            }

            $accessToken = $flowData['tokens']['access_token'];
            $provider = $flowData['provider'] ?? 'unknown';
            
            // Bestimme Test-Endpoint basierend auf Provider
            $testEndpoint = $this->getTestEndpoint($provider, $flowData);
            
            if (!$testEndpoint) {
                return response()->json([
                    'success' => false,
                    'error' => 'Kein Test-Endpoint für diesen Provider verfügbar'
                ], 400);
            }

            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->get($testEndpoint);

            return response()->json([
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'endpoint' => $testEndpoint,
                'response_data' => $response->json(),
                'message' => $response->successful() ? 'API-Aufruf erfolgreich' : 'API-Aufruf fehlgeschlagen'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'API test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send tokens via email
     */
    public function sendTokensEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $flowData = session('oauth2_flow');
        
        if (!$flowData || !isset($flowData['tokens'])) {
            return response()->json([
                'success' => false,
                'error' => 'Keine Tokens zum Senden gefunden'
            ], 400);
        }

        try {
            Mail::to($request->email)->send(new OAuth2TokenMail($flowData));

            return response()->json([
                'success' => true,
                'message' => 'Tokens erfolgreich an ' . $request->email . ' gesendet'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'E-Mail-Versand fehlgeschlagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current flow data
     */
    public function getFlowData(): JsonResponse
    {
        $flowData = session('oauth2_flow', []);
        
        // Don't expose sensitive data like client_secret
        if (isset($flowData['client_secret'])) {
            $flowData['client_secret'] = '***HIDDEN***';
        }

        return response()->json($flowData);
    }

    /**
     * Reset the OAuth2 flow
     */
    public function resetFlow(): JsonResponse
    {
        session()->forget('oauth2_flow');
        
        return response()->json([
            'success' => true,
            'message' => 'OAuth2 Flow erfolgreich zurückgesetzt'
        ]);
    }

    /**
     * Detect OAuth2 provider based on URL
     */
    private function detectProvider(string $authUrl): string
    {
        if (str_contains($authUrl, 'passolution')) {
            return 'passolution';
        } elseif (str_contains($authUrl, 'google')) {
            return 'google';
        } elseif (str_contains($authUrl, 'microsoft')) {
            return 'microsoft';
        } elseif (str_contains($authUrl, 'github')) {
            return 'github';
        }
        
        return 'custom';
    }

    /**
     * Get test endpoint for API call
     */
    private function getTestEndpoint(string $provider, array $flowData): ?string
    {
        switch ($provider) {
            case 'passolution':
                return config('oauth2.default_providers.passolution.api_base_url') . '/user';
            case 'google':
                return 'https://www.googleapis.com/oauth2/v2/userinfo';
            case 'github':
                return 'https://api.github.com/user';
            case 'microsoft':
                return 'https://graph.microsoft.com/v1.0/me';
            default:
                return null;
        }
    }

    /**
     * Simulate token response for demo purposes
     */
    private function simulateTokenResponse(string $provider = 'passolution'): array
    {
        $baseToken = [
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.' . base64_encode(json_encode([
                'sub' => '12345',
                'name' => 'Demo User',
                'email' => 'demo@passolution.de',
                'iat' => time(),
                'exp' => time() + 3600,
                'scope' => 'default',
                'provider' => $provider
            ])) . '.signature_demo',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'rt_' . Str::random(40),
            'scope' => 'default'
        ];

        // Provider-spezifische Anpassungen
        if ($provider === 'passolution') {
            $baseToken['api_version'] = 'v2';
            $baseToken['user_id'] = '123456';
        }

        return $baseToken;
    }

    /**
     * Simulate refresh token response for demo purposes
     */
    private function simulateRefreshTokenResponse(string $provider = 'passolution'): array
    {
        $baseToken = [
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.' . base64_encode(json_encode([
                'sub' => '12345',
                'name' => 'Demo User',
                'email' => 'demo@passolution.de',
                'iat' => time(),
                'exp' => time() + 3600,
                'scope' => 'default',
                'provider' => $provider
            ])) . '.new_signature_demo',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'default'
        ];

        if ($provider === 'passolution') {
            $baseToken['api_version'] = 'v2';
        }

        return $baseToken;
    }
}