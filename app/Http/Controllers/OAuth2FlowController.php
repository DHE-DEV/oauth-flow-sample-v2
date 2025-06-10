<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
            'scope' => $request->scope ?? 'read write',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

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
                'scope' => $request->scope ?? 'read write',
                'provider' => $this->detectProvider($request->authorization_endpoint),
            ]
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
    }

    /**
     * Step 2: Handle authorization callback simulation
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $request->validate([
            'authorization_code' => 'required|string',
            'state' => 'required|string',
        ]);

        $flowData = session('oauth2_flow');
        
        if (!$flowData || $flowData['state'] !== $request->state) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid state parameter - Security check failed',
                'step' => 2
            ], 400);
        }

        // Store the authorization code
        session(['oauth2_flow.authorization_code' => $request->authorization_code]);

        return response()->json([
            'success' => true,
            'authorization_code' => $request->authorization_code,
            'state_verified' => true,
            'step' => 2,
            'message' => 'Authorization Code empfangen und State-Parameter verifiziert'
        ]);
    }

    /**
     * Step 3: Exchange authorization code for tokens
     */
    public function exchangeCodeForTokens(Request $request): JsonResponse
    {
        $flowData = session('oauth2_flow');
        
        if (!$flowData || !isset($flowData['authorization_code'])) {
            return response()->json([
                'success' => false,
                'error' => 'Kein Authorization Code in der Session gefunden',
                'step' => 3
            ], 400);
        }

        try {
            // Simulate token exchange (in real scenario, this would be a real API call)
            if ($request->has('simulate') && $request->simulate) {
                // Simulate successful token exchange
                $tokens = $this->simulateTokenResponse($flowData['provider'] ?? 'passolution');
            } else {
                // Make real API call to token endpoint
                $tokenData = [
                    'grant_type' => 'authorization_code',
                    'client_id' => $flowData['client_id'],
                    'client_secret' => $flowData['client_secret'],
                    'code' => $flowData['authorization_code'],
                    'redirect_uri' => $flowData['redirect_uri'],
                    'code_verifier' => $flowData['code_verifier'],
                ];

                // Spezielle Header für Passolution
                $headers = [];
                if (str_contains($flowData['token_endpoint'], 'passolution')) {
                    $headers['Accept'] = 'application/json';
                    $headers['User-Agent'] = 'OAuth2-Flow-Visualizer/1.0';
                }

                $response = Http::asForm()
                    ->withHeaders($headers)
                    ->timeout(30)
                    ->post($flowData['token_endpoint'], $tokenData);

                if (!$response->successful()) {
                    $errorBody = $response->json();
                    return response()->json([
                        'success' => false,
                        'error' => 'Token exchange failed: ' . ($errorBody['error_description'] ?? $response->body()),
                        'step' => 3,
                        'http_status' => $response->status()
                    ], 400);
                }

                $tokens = $response->json();
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
        $flowData = session('oauth2_flow');
        
        if (!$flowData || !isset($flowData['tokens']['refresh_token'])) {
            return response()->json([
                'success' => false,
                'error' => 'Kein Refresh Token verfügbar',
                'step' => 4
            ], 400);
        }

        try {
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
                $headers = [];
                if (str_contains($flowData['token_endpoint'], 'passolution')) {
                    $headers['Accept'] = 'application/json';
                    $headers['User-Agent'] = 'OAuth2-Flow-Visualizer/1.0';
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
        $flowData = session('oauth2_flow');
        
        if (!$flowData || !isset($flowData['tokens']['access_token'])) {
            return response()->json([
                'success' => false,
                'error' => 'Kein Access Token verfügbar'
            ], 400);
        }

        try {
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
                'scope' => 'read write',
                'provider' => $provider
            ])) . '.signature_demo',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'rt_' . Str::random(40),
            'scope' => 'read write'
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
                'scope' => 'read write',
                'provider' => $provider
            ])) . '.new_signature_demo',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'read write'
        ];

        if ($provider === 'passolution') {
            $baseToken['api_version'] = 'v2';
        }

        return $baseToken;
    }
}