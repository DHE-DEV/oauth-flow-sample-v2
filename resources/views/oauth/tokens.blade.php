<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Tokens - Passolution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .token-card {
            background: rgba(102, 126, 234, 0.05);
            border: 2px solid rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            position: relative;
        }
        .token-value {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.05);
            padding: 10px;
            border-radius: 5px;
            max-height: 150px;
            overflow-y: auto;
        }
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(102, 126, 234, 0.8);
            border: none;
            color: white;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .copy-btn:hover {
            background: rgba(102, 126, 234, 1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .btn-success {
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .success-animation {
            animation: successPulse 0.6s ease-in-out;
        }
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .header-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 3rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle header-icon"></i>
                    <h1 class="text-white mt-3 mb-2">OAuth2 Tokens Generiert</h1>
                    <p class="text-white-50">Ihre Tokens wurden erfolgreich erstellt</p>
                </div>

                <div class="card">
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Client ID:</h6>
                                <p class="fw-semibold">{{ $clientId }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Generiert am:</h6>
                                <p class="fw-semibold">{{ $timestamp }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="token-card p-3">
                                    <button class="copy-btn" onclick="copyToClipboard('access_token')">
                                        <i class="fas fa-copy"></i> Kopieren
                                    </button>
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-key me-2"></i>Access Token
                                    </h6>
                                    <div class="token-value" id="access_token">{{ $tokenData['access_token'] ?? 'N/A' }}</div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <strong>Type:</strong> {{ $tokenData['token_type'] ?? 'N/A' }} | 
                                            <strong>Expires in:</strong> {{ $tokenData['expires_in'] ?? 'N/A' }} Sekunden
                                        </small>
                                    </div>
                                </div>
                            </div>

                            @if(isset($tokenData['refresh_token']))
                            <div class="col-12 mb-4">
                                <div class="token-card p-3">
                                    <button class="copy-btn" onclick="copyToClipboard('refresh_token')">
                                        <i class="fas fa-copy"></i> Kopieren
                                    </button>
                                    <h6 class="text-success mb-2">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh Token
                                    </h6>
                                    <div class="token-value" id="refresh_token">{{ $tokenData['refresh_token'] }}</div>
                                </div>
                            </div>
                            @endif

                            @if(isset($tokenData['scope']))
                            <div class="col-12 mb-4">
                                <div class="token-card p-3">
                                    <h6 class="text-info mb-2">
                                        <i class="fas fa-list me-2"></i>Scope
                                    </h6>
                                    <div class="token-value">{{ $tokenData['scope'] }}</div>
                                </div>
                            </div>
                            @endif

                            <div class="col-12 mb-4">
                                <div class="token-card p-3">
                                    <button class="copy-btn" onclick="copyToClipboard('full_response')">
                                        <i class="fas fa-copy"></i> Kopieren
                                    </button>
                                    <h6 class="text-secondary mb-2">
                                        <i class="fas fa-code me-2"></i>Vollständige Antwort (JSON)
                                    </h6>
                                    <div class="token-value" id="full_response">{{ json_encode($tokenData, JSON_PRETTY_PRINT) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-4">
                            <h5 class="mb-3"><i class="fas fa-paper-plane text-primary me-2"></i>Tokens per E-Mail senden</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="email" 
                                           class="form-control" 
                                           id="email_address" 
                                           placeholder="E-Mail-Adresse eingeben"
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" 
                                            class="btn btn-success w-100" 
                                            onclick="sendTokensEmail()">
                                        <i class="fas fa-envelope me-2"></i>Senden
                                    </button>
                                </div>
                            </div>
                            <div id="email_status" class="mt-2"></div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{{ route('oauth.index') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Neue Tokens generieren
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="copyToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong class="me-auto">Erfolgreich</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Der Inhalt wurde in die Zwischenablage kopiert!
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // CSRF Token Setup
        window.Laravel = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                // Show success animation
                element.parentElement.classList.add('success-animation');
                
                // Show toast
                const toast = new bootstrap.Toast(document.getElementById('copyToast'));
                toast.show();
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    element.parentElement.classList.remove('success-animation');
                }, 600);
            }).catch(function(err) {
                console.error('Fehler beim Kopieren: ', err);
                alert('Fehler beim Kopieren in die Zwischenablage');
            });
        }

        function sendTokensEmail() {
            const email = document.getElementById('email_address').value;
            const statusDiv = document.getElementById('email_status');
            
            if (!email) {
                statusDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Bitte geben Sie eine E-Mail-Adresse ein.</div>';
                return;
            }

            const tokenData = @json($tokenData);
            const clientId = @json($clientId);
            const timestamp = @json($timestamp);

            // Show loading state
            statusDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>E-Mail wird gesendet...</div>';

            fetch('{{ route("oauth.send-tokens") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.Laravel.csrfToken
                },
                body: JSON.stringify({
                    email: email,
                    token_data: JSON.stringify(tokenData),
                    client_id: clientId,
                    timestamp: timestamp
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
                    document.getElementById('email_address').value = '';
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Fehler beim Senden der E-Mail.</div>';
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>