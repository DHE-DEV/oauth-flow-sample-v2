<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OAuth2 Flow Visualizer - Passolution</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .step-card {
            transition: all 0.3s ease;
        }
        .step-card.active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        .step-card.completed {
            border-color: #10b981;
            background-color: rgba(16, 185, 129, 0.05);
        }
        .flow-arrow {
            opacity: 0.3;
            transition: opacity 0.3s ease;
        }
        .flow-arrow.active {
            opacity: 1;
        }
        .json-display {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .provider-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .provider-passolution {
            background-color: #1e40af;
            color: white;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-key text-blue-600 mr-3"></i>
                OAuth2 Flow Visualizer
            </h1>
            <p class="text-gray-600 mb-4">Visualisierung des OAuth2 Authorization Code Flow mit PKCE</p>
            <div class="provider-badge provider-passolution">
                <i class="fas fa-shield-alt mr-1"></i>
                Passolution Integration
            </div>
        </div>

        <!-- Reset Button -->
        <div class="text-center mb-6">
            <button id="resetFlow" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-refresh mr-2"></i>Flow Zurücksetzen
            </button>
        </div>

        <!-- Configuration Panel -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-cog text-blue-600 mr-2"></i>OAuth2 Konfiguration
                <span class="text-sm font-normal text-gray-500">(Passolution vorkonfiguriert)</span>
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Client ID *
                        <span class="text-xs text-gray-500">(Von Passolution erhalten)</span>
                    </label>
                    <input type="text" id="clientId" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ihre Passolution Client ID">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Client Secret *
                        <span class="text-xs text-gray-500">(Von Passolution erhalten)</span>
                    </label>
                    <input type="password" id="clientSecret" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ihr Passolution Client Secret">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Authorization Endpoint *</label>
                    <input type="url" id="authEndpoint" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" value="https://web.passolution.eu/en/oauth/authorize" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Token Endpoint *</label>
                    <input type="url" id="tokenEndpoint" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" value="https://web.passolution.eu/en/oauth/token" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Redirect URI *</label>
                    <select id="redirectUri" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="https://api-client-oauth2-v2.passolution.de/oauth/callback">api-client-oauth2-v2.passolution.de/oauth/callback</option>
                        <option value="https://api-client-oauth2-v2.passolution.de/oauth/callback">api-client-oauth2-v2.passolution.de/oauth/callback</option>
                        <option value="https://api-client-oauth2-v2.passolution.de">api-client-oauth2-v2.passolution.de (Root)</option>
                        <option value="http://localhost:8000/oauth/callback">localhost:8000/oauth/callback (Local)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Wählen Sie die EXAKT gleiche URI, die in Passolution registriert ist</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scope (Optional)</label>
                    <input type="text" id="scope" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Leer lassen für Standard-Berechtigungen" value="">
                    <p class="text-xs text-gray-500 mt-1">Standard: Keine spezifischen Scopes (Passolution Standard-Berechtigungen)</p>
                </div>
            </div>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Die Passolution-Endpoints sind bereits vorkonfiguriert. Sie müssen nur Ihre Client ID und Client Secret eingeben.
                </p>
            </div>
        </div>

        <!-- OAuth2 Flow Steps -->
        <div class="space-y-6">
            <!-- Step 1: Authorization URL Generation -->
            <div class="step-card bg-white rounded-lg shadow-lg p-6 border-2" id="step1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <span class="bg-blue-500 text-white rounded-full w-8 h-8 inline-flex items-center justify-center mr-3">1</span>
                        Authorization URL Generierung
                    </h3>
                    <button id="generateAuthUrl" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-link mr-2"></i>URL Generieren
                    </button>
                </div>
                <div class="mb-4 text-sm text-gray-600">
                    <p>Generiert eine sichere Authorization URL mit PKCE (Proof Key for Code Exchange) für erhöhte Sicherheit.</p>
                </div>
                <div id="step1Result" class="hidden">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Generierte Authorization URL:</h4>
                        <div class="bg-white p-3 rounded border break-all">
                            <a id="authUrlLink" href="#" target="_blank" class="text-blue-600 hover:underline"></a>
                            <button onclick="copyToClipboard('authUrlLink')" class="ml-2 text-blue-500 hover:text-blue-700">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State Parameter:</label>
                                <input type="text" id="stateValue" class="w-full p-2 bg-gray-100 border rounded text-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Code Challenge (PKCE):</label>
                                <input type="text" id="codeChallengeValue" class="w-full p-2 bg-gray-100 border rounded text-sm" readonly>
                            </div>
                        </div>
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-700">
                                <i class="fas fa-external-link-alt mr-2"></i>
                                Öffnen Sie die URL in einem neuen Tab, autorisieren Sie die Anwendung und kopieren Sie den Authorization Code aus der Callback-URL.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flow Arrow -->
            <div class="text-center flow-arrow" id="arrow1">
                <i class="fas fa-arrow-down text-4xl text-gray-300"></i>
                <p class="text-sm text-gray-500 mt-2">Benutzer wird zu Passolution weitergeleitet</p>
            </div>

            <!-- Step 2: Authorization Callback -->
            <div class="step-card bg-white rounded-lg shadow-lg p-6 border-2" id="step2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <span class="bg-gray-400 text-white rounded-full w-8 h-8 inline-flex items-center justify-center mr-3">2</span>
                        Authorization Callback
                    </h3>
                </div>
                <div class="mb-4 text-sm text-gray-600">
                    <p>Nach der Autorisierung bei Passolution erhalten Sie einen Authorization Code in der Callback-URL.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Authorization Code *</label>
                        <input type="text" id="authCode" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Hier den erhaltenen Authorization Code eingeben">
                        <p class="text-xs text-gray-500 mt-1">Aus der URL: ?code=DIESER_WERT&state=...</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">State (automatisch gefüllt)</label>
                        <input type="text" id="callbackState" class="w-full p-3 bg-gray-100 border border-gray-300 rounded-lg" readonly>
                        <p class="text-xs text-gray-500 mt-1">Wird zur Sicherheitsvalidierung verwendet</p>
                    </div>
                </div>
                <button id="handleCallback" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200 disabled:bg-gray-400" disabled>
                    <i class="fas fa-check mr-2"></i>Callback Verarbeiten
                </button>
                <div id="step2Result" class="hidden mt-4">
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <h4 class="font-semibold text-green-700 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>Authorization Code erfolgreich empfangen!
                        </h4>
                        <p class="text-green-600">State Parameter wurde erfolgreich verifiziert. Sicherheitscheck bestanden.</p>
                    </div>
                </div>
            </div>

            <!-- Flow Arrow -->
            <div class="text-center flow-arrow" id="arrow2">
                <i class="fas fa-arrow-down text-4xl text-gray-300"></i>
                <p class="text-sm text-gray-500 mt-2">Authorization Code wird gegen Tokens getauscht</p>
            </div>

            <!-- Step 3: Token Exchange -->
            <div class="step-card bg-white rounded-lg shadow-lg p-6 border-2" id="step3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <span class="bg-gray-400 text-white rounded-full w-8 h-8 inline-flex items-center justify-center mr-3">3</span>
                        Token Exchange
                    </h3>
                    <div class="space-x-2">
                        <button id="exchangeTokens" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition duration-200 disabled:bg-gray-400" disabled>
                            <i class="fas fa-exchange-alt mr-2"></i>Echte API
                        </button>
                        <button id="simulateTokens" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition duration-200 disabled:bg-gray-400" disabled>
                            <i class="fas fa-magic mr-2"></i>Simulieren
                        </button>
                    </div>
                </div>
                <div class="mb-4 text-sm text-gray-600">
                    <p>Tauscht den Authorization Code gegen Access Token und Refresh Token. Wählen Sie zwischen echter API oder Simulation.</p>
                </div>
                <div id="step3Result" class="hidden">
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <h4 class="font-semibold text-purple-700 mb-2">
                            <i class="fas fa-key mr-2"></i>Tokens erfolgreich erhalten:
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token:</label>
                                <div class="bg-white p-3 rounded border break-all relative">
                                    <code id="accessToken" class="json-display text-xs"></code>
                                    <button onclick="copyToClipboard('accessToken')" class="absolute top-2 right-2 text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Refresh Token:</label>
                                <div class="bg-white p-3 rounded border break-all relative">
                                    <code id="refreshToken" class="json-display text-xs"></code>
                                    <button onclick="copyToClipboard('refreshToken')" class="absolute top-2 right-2 text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Token Type:</label>
                                    <div class="bg-white p-2 rounded border">
                                        <span id="tokenType" class="text-sm"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gültigkeitsdauer:</label>
                                    <div class="bg-white p-2 rounded border">
                                        <span id="expiresIn" class="text-sm"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Scope:</label>
                                    <div class="bg-white p-2 rounded border">
                                        <span id="tokenScope" class="text-sm"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button id="testApiCall" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition duration-200">
                                    <i class="fas fa-flask mr-2"></i>API Test durchführen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flow Arrow -->
            <div class="text-center flow-arrow" id="arrow3">
                <i class="fas fa-arrow-down text-4xl text-gray-300"></i>
                <p class="text-sm text-gray-500 mt-2">Token kann bei Bedarf erneuert werden</p>
            </div>

            <!-- Step 4: Token Refresh -->
            <div class="step-card bg-white rounded-lg shadow-lg p-6 border-2" id="step4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">
                        <span class="bg-gray-400 text-white rounded-full w-8 h-8 inline-flex items-center justify-center mr-3">4</span>
                        Token Refresh (Optional)
                    </h3>
                    <div class="space-x-2">
                        <button id="refreshTokenBtn" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition duration-200 disabled:bg-gray-400" disabled>
                            <i class="fas fa-sync mr-2"></i>Echte API
                        </button>
                        <button id="simulateRefresh" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition duration-200 disabled:bg-gray-400" disabled>
                            <i class="fas fa-magic mr-2"></i>Simulieren
                        </button>
                    </div>
                </div>
                <div class="mb-4 text-sm text-gray-600">
                    <p>Erneuert den Access Token mit Hilfe des Refresh Tokens, ohne dass der Benutzer sich erneut anmelden muss.</p>
                </div>
                <div id="step4Result" class="hidden">
                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200">
                        <h4 class="font-semibold text-indigo-700 mb-2">
                            <i class="fas fa-sync mr-2"></i>Token erfolgreich erneuert:
                        </h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Neuer Access Token:</label>
                            <div class="bg-white p-3 rounded border break-all relative">
                                <code id="newAccessToken" class="json-display text-xs"></code>
                                <button onclick="copyToClipboard('newAccessToken')" class="absolute top-2 right-2 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Test Results -->
            <div id="apiTestSection" class="step-card bg-white rounded-lg shadow-lg p-6 border-2 hidden">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-flask text-indigo-600 mr-2"></i>API Test Ergebnisse
                </h3>
                <div id="apiTestResult"></div>
            </div>
        </div>

        <!-- Email Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                <i class="fas fa-envelope text-blue-600 mr-2"></i>Tokens per E-Mail senden
            </h2>
            <div class="flex space-x-4">
                <input type="email" id="emailAddress" class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="E-Mail-Adresse eingeben">
                <button id="sendEmail" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition duration-200 disabled:bg-gray-400" disabled>
                    <i class="fas fa-paper-plane mr-2"></i>Senden
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                Die Tokens werden sicher per E-Mail übertragen und enthalten alle notwendigen Informationen für die API-Nutzung.
            </p>
        </div>

        <!-- Success/Error Messages -->
        <div id="messages" class="mt-4"></div>
    </div>

    <script>
        // CSRF Token für alle AJAX Requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Aktuelle Flow-Daten
        let flowData = {};

        // Elemente
        const elements = {
            generateAuthUrl: document.getElementById('generateAuthUrl'),
            handleCallback: document.getElementById('handleCallback'),
            exchangeTokens: document.getElementById('exchangeTokens'),
            simulateTokens: document.getElementById('simulateTokens'),
            refreshTokenBtn: document.getElementById('refreshTokenBtn'),
            simulateRefresh: document.getElementById('simulateRefresh'),
            sendEmail: document.getElementById('sendEmail'),
            resetFlow: document.getElementById('resetFlow'),
            testApiCall: document.getElementById('testApiCall'),
            // Form inputs
            clientId: document.getElementById('clientId'),
            clientSecret: document.getElementById('clientSecret'),
            authEndpoint: document.getElementById('authEndpoint'),
            tokenEndpoint: document.getElementById('tokenEndpoint'),
            redirectUri: document.getElementById('redirectUri'),
            scope: document.getElementById('scope'),
            authCode: document.getElementById('authCode'),
            emailAddress: document.getElementById('emailAddress')
        };

        // Event Listeners
        elements.generateAuthUrl.addEventListener('click', generateAuthUrl);
        elements.handleCallback.addEventListener('click', handleCallback);
        elements.exchangeTokens.addEventListener('click', () => exchangeTokens(false));
        elements.simulateTokens.addEventListener('click', () => exchangeTokens(true));
        elements.refreshTokenBtn.addEventListener('click', () => refreshToken(false));
        elements.simulateRefresh.addEventListener('click', () => refreshToken(true));
        elements.sendEmail.addEventListener('click', sendTokensEmail);
        elements.resetFlow.addEventListener('click', resetFlow);
        elements.testApiCall.addEventListener('click', testApiCall);

        // Step 1: Authorization URL Generation
        async function generateAuthUrl() {
            if (!validateStep1()) return;

            try {
                showLoading('URL wird generiert...');
                
                const response = await fetch('/oauth2/generate-auth-url', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        client_id: elements.clientId.value,
                        client_secret: elements.clientSecret.value,
                        authorization_endpoint: elements.authEndpoint.value,
                        token_endpoint: elements.tokenEndpoint.value,
                        redirect_uri: elements.redirectUri.value,
                        scope: elements.scope.value
                    })
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    showStep1Result(data);
                    activateStep(2);
                    showMessage('Authorization URL erfolgreich generiert!', 'success');
                } else {
                    showMessage('Fehler: ' + data.error, 'error');
                }
            } catch (error) {
                hideLoading();
                showMessage('Fehler beim Generieren der URL: ' + error.message, 'error');
            }
        }

        // Step 2: Handle Callback
        async function handleCallback() {
            if (!elements.authCode.value.trim()) {
                showMessage('Bitte geben Sie den Authorization Code ein.', 'error');
                return;
            }

            try {
                showLoading('Callback wird verarbeitet...');

                const response = await fetch('/oauth2/handle-callback', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        authorization_code: elements.authCode.value,
                        state: document.getElementById('callbackState').value
                    })
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    showStep2Result();
                    activateStep(3);
                    showMessage('Authorization Code erfolgreich verarbeitet!', 'success');
                } else {
                    showMessage('Fehler: ' + data.error, 'error');
                }
            } catch (error) {
                hideLoading();
                showMessage('Fehler beim Verarbeiten des Callbacks: ' + error.message, 'error');
            }
        }

        // Step 3: Exchange Tokens
        async function exchangeTokens(simulate = false) {
            try {
                showLoading(simulate ? 'Tokens werden simuliert...' : 'Tokens werden ausgetauscht...');

                const response = await fetch('/oauth2/exchange-tokens', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        simulate: simulate
                    })
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    showStep3Result(data.tokens);
                    activateStep(4);
                    enableEmailSection();
                    showMessage(simulate ? 'Demo-Tokens generiert!' : 'Tokens erfolgreich erhalten!', 'success');
                } else {
                    showMessage('Fehler: ' + data.error, 'error');
                }
            } catch (error) {
                hideLoading();
                showMessage('Fehler beim Token-Austausch: ' + error.message, 'error');
            }
        }

        // Step 4: Refresh Token
        async function refreshToken(simulate = false) {
            try {
                showLoading(simulate ? 'Token-Refresh wird simuliert...' : 'Token wird erneuert...');

                const response = await fetch('/oauth2/refresh-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        simulate: simulate
                    })
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    showStep4Result(data.tokens);
                    showMessage('Token erfolgreich erneuert!', 'success');
                } else {
                    showMessage('Fehler: ' + data.error, 'error');
                }
            } catch (error) {
                hideLoading();
                showMessage('Fehler beim Token-Refresh: ' + error.message, 'error');
            }
        }

        // API Test
        async function testApiCall() {
            try {
                showLoading('API wird getestet...');

                const response = await fetch('/oauth2/test-api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();
                hideLoading();

                showApiTestResult(data);
                showMessage(data.success ? 'API-Test erfolgreich!' : 'API-Test fehlgeschlagen!', data.success ? 'success' : 'error');
            } catch (error) {
                hideLoading();
                showMessage('Fehler beim API-Test: ' + error.message, 'error');
            }
        }

        // Send Tokens via Email
        async function sendTokensEmail() {
            if (!elements.emailAddress.value.trim()) {
                showMessage('Bitte geben Sie eine E-Mail-Adresse ein.', 'error');
                return;
            }

            try {
                showLoading('E-Mail wird gesendet...');

                const response = await fetch('/oauth2/send-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        email: elements.emailAddress.value
                    })
                });

                const data = await response.json();
                hideLoading();

                if (data.success) {
                    showMessage('Tokens erfolgreich per E-Mail gesendet!', 'success');
                    elements.emailAddress.value = '';
                } else {
                    showMessage('Fehler: ' + data.error, 'error');
                }
            } catch (error) {
                hideLoading();
                showMessage('Fehler beim Senden der E-Mail: ' + error.message, 'error');
            }
        }

        // Reset Flow
        async function resetFlow() {
            if (!confirm('Möchten Sie den OAuth2 Flow wirklich zurücksetzen? Alle Daten gehen verloren.')) {
                return;
            }

            try {
                const response = await fetch('/oauth2/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                showMessage('Fehler beim Zurücksetzen: ' + error.message, 'error');
            }
        }

        // Validation Functions
        function validateStep1() {
            const required = ['clientId', 'clientSecret'];
            for (const field of required) {
                if (!elements[field].value.trim()) {
                    showMessage(`Bitte füllen Sie das Feld "${field}" aus.`, 'error');
                    elements[field].focus();
                    return false;
                }
            }
            return true;
        }

        // Display Functions
        function showStep1Result(data) {
            document.getElementById('step1Result').classList.remove('hidden');
            document.getElementById('authUrlLink').href = data.auth_url;
            document.getElementById('authUrlLink').textContent = data.auth_url;
            document.getElementById('stateValue').value = data.state;
            document.getElementById('codeChallengeValue').value = data.code_challenge;
            document.getElementById('callbackState').value = data.state;
            
            completeStep(1);
        }

        function showStep2Result() {
            document.getElementById('step2Result').classList.remove('hidden');
            completeStep(2);
        }

        function showStep3Result(tokens) {
            document.getElementById('step3Result').classList.remove('hidden');
            document.getElementById('accessToken').textContent = tokens.access_token;
            document.getElementById('refreshToken').textContent = tokens.refresh_token || 'Nicht verfügbar';
            document.getElementById('tokenType').textContent = tokens.token_type || 'Bearer';
            document.getElementById('expiresIn').textContent = (tokens.expires_in || 3600) + ' Sekunden';
            document.getElementById('tokenScope').textContent = tokens.scope || 'Nicht spezifiziert';
            
            completeStep(3);
        }

        function showStep4Result(tokens) {
            document.getElementById('step4Result').classList.remove('hidden');
            document.getElementById('newAccessToken').textContent = tokens.access_token;
            completeStep(4);
        }

        function showApiTestResult(data) {
            const section = document.getElementById('apiTestSection');
            const result = document.getElementById('apiTestResult');
            
            section.classList.remove('hidden');
            
            const statusColor = data.success ? 'green' : 'red';
            const statusIcon = data.success ? 'check-circle' : 'exclamation-circle';
            
            result.innerHTML = `
                <div class="bg-${statusColor}-50 p-4 rounded-lg border border-${statusColor}-200">
                    <h4 class="font-semibold text-${statusColor}-700 mb-2">
                        <i class="fas fa-${statusIcon} mr-2"></i>API Test - ${data.success ? 'Erfolgreich' : 'Fehlgeschlagen'}
                    </h4>
                    <div class="space-y-2">
                        <p><strong>Endpoint:</strong> ${data.endpoint || 'Nicht verfügbar'}</p>
                        <p><strong>HTTP Status:</strong> ${data.status_code || 'Unbekannt'}</p>
                        ${data.response_data ? `
                            <div>
                                <strong>Antwort:</strong>
                                <pre class="bg-white p-3 rounded border mt-2 text-xs overflow-auto">${JSON.stringify(data.response_data, null, 2)}</pre>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function activateStep(stepNumber) {
            // Remove active class from all steps
            document.querySelectorAll('.step-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Add active class to current step
            document.getElementById(`step${stepNumber}`).classList.add('active');
            
            // Enable buttons based on step
            switch (stepNumber) {
                case 2:
                    elements.handleCallback.disabled = false;
                    document.getElementById('arrow1').classList.add('active');
                    break;
                case 3:
                    elements.exchangeTokens.disabled = false;
                    elements.simulateTokens.disabled = false;
                    document.getElementById('arrow2').classList.add('active');
                    break;
                case 4:
                    elements.refreshTokenBtn.disabled = false;
                    elements.simulateRefresh.disabled = false;
                    document.getElementById('arrow3').classList.add('active');
                    break;
            }
        }

        function completeStep(stepNumber) {
            document.getElementById(`step${stepNumber}`).classList.add('completed');
            // Update step number circle color
            const stepCircle = document.querySelector(`#step${stepNumber} .rounded-full`);
            stepCircle.classList.remove('bg-gray-400');
            stepCircle.classList.add('bg-green-500');
        }

        function enableEmailSection() {
            elements.sendEmail.disabled = false;
        }

        function showMessage(message, type = 'info') {
            // Remove existing notifications
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            const colors = {
                success: 'bg-green-100 border-green-500 text-green-700',
                error: 'bg-red-100 border-red-500 text-red-700',
                info: 'bg-blue-100 border-blue-500 text-blue-700'
            };
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle'
            };
            
            notification.className = `notification border-l-4 ${colors[type]}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="${icons[type]}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        function showLoading(message) {
            // Implementation for loading indicator
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'loadingIndicator';
            loadingDiv.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            loadingDiv.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-spinner fa-spin mr-3 text-blue-600"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingDiv);
        }

        function hideLoading() {
            const loading = document.getElementById('loadingIndicator');
            if (loading) {
                loading.remove();
            }
        }

        // Copy to Clipboard Function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            let text;
            
            if (element.tagName === 'A') {
                text = element.href;
            } else {
                text = element.textContent || element.value;
            }
            
            navigator.clipboard.writeText(text).then(() => {
                showMessage('In die Zwischenablage kopiert!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showMessage('In die Zwischenablage kopiert!', 'success');
            });
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on Client ID field
            elements.clientId.focus();
            
            // Add tooltips or help text if needed
            console.log('OAuth2 Flow Visualizer für Passolution initialisiert');
        });
    </script>
</body>
</html>