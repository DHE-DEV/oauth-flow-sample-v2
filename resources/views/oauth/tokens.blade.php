<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Tokens - Passolution API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .token-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 14px;
            max-height: 150px;
            overflow-y: auto;
        }
        .copy-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 5px;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        .copy-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 10px rgba(40, 167, 69, 0.3);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
        }
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
        }
        .success-icon {
            color: #28a745;
            font-size: 3rem;
        }
        .token-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        .token-card:hover {
            transform: translateY(-2px);
        }
        .email-modal .modal-content {
            border-radius: 15px;
            border: none;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="main-container p-4 p-md-5">
            <div class="text-center mb-5">
                <i class="fas fa-check-circle success-icon mb-3"></i>
                <h1 class="display-4 mb-3 text-success">OAuth2 Flow erfolgreich!</h1>
                <p class="lead text-muted">Ihre Access Tokens wurden erfolgreich generiert</p>
                <small class="text-muted">Erstellt am: {{ \Carbon\Carbon::parse($timestamp)->setTimezone('Europe/Berlin')->format('d.m.Y H:i:s') }} (MEZ/MESZ)</small>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card token-card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-primary">
                                <i class="fas fa-id-card"></i> Client Information
                            </h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Client ID:</label>
                                <div class="token-display p-2">{{ $clientId }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Timestamp:</label>
                                <div class="token-display p-2">{{ \Carbon\Carbon::parse($timestamp)->setTimezone('Europe/Berlin')->format('d.m.Y H:i:s') }} (MEZ/MESZ)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card token-card h-100">
                        <div class="card-body">
                            <h5 class="card-title text-primary">
                                <i class="fas fa-info-circle"></i> Token Information
                            </h5>
                            @if(isset($tokenData['token_type']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">Token Type:</label>
                                <div class="token-display p-2">{{ $tokenData['token_type'] }}</div>
                            </div>
                            @endif
                            @if(isset($tokenData['expires_in']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">Gültigkeitsdauer:</label>
                                <div class="token-display p-2">{{ $tokenData['expires_in'] }} Sekunden (bis {{ \Carbon\Carbon::now()->addSeconds($tokenData['expires_in'])->setTimezone('Europe/Berlin')->format('d.m.Y H:i:s') }} (MEZ/MESZ))</div>
                            </div>
                            @endif
                            @if(isset($tokenData['scope']))
                            <div class="mb-3">
                                <label class="form-label fw-bold">Scope:</label>
                                <div class="token-display p-2">{{ $tokenData['scope'] ?: 'Keine spezifischen Scopes' }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card token-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title text-primary mb-0">
                                    <i class="fas fa-key"></i> Access Token
                                </h5>
                                <button class="copy-btn" onclick="copyToClipboard('access_token')">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </div>
                            <div id="access_token" class="token-display p-3">{{ $tokenData['access_token'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($tokenData['refresh_token']))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card token-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title text-primary mb-0">
                                    <i class="fas fa-redo"></i> Refresh Token
                                </h5>
                                <button class="copy-btn" onclick="copyToClipboard('refresh_token')">
                                    <i class="fas fa-copy"></i> Kopieren
                                </button>
                            </div>
                            <div id="refresh_token" class="token-display p-3">{{ $tokenData['refresh_token'] }}</div>
                            @if(isset($tokenData['refresh_token_expires_in']))
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> 
                                    Refresh Token läuft ab: {{ \Carbon\Carbon::now()->addSeconds($tokenData['refresh_token_expires_in'])->setTimezone('Europe/Berlin')->format('d.m.Y H:i:s') }} (MEZ/MESZ)
                                    <br>Gültigkeitsdauer: {{ $tokenData['refresh_token_expires_in'] }} Sekunden
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card token-card">
                        <div class="card-body">
                            <h5 class="card-title text-primary">
                                <i class="fas fa-code"></i> API Verwendung
                            </h5>
                            <p class="text-muted">So verwenden Sie den Access Token für API-Aufrufe:</p>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Base URL:</label>
                                <div class="token-display p-2">https://api.passolution.eu/api/v2</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Authorization Header:</label>
                                <div class="d-flex align-items-center">
                                    <div id="auth_header" class="token-display p-2 flex-grow-1 me-2">Authorization: Bearer {{ $tokenData['access_token'] }}</div>
                                    <button class="copy-btn" onclick="copyToClipboard('auth_header')">
                                        <i class="fas fa-copy"></i> Kopieren
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Sicherheitshinweis:</strong> 
                        Bewahren Sie diese Tokens sicher auf und teilen Sie sie niemals öffentlich. 
                        Verwenden Sie immer HTTPS für API-Aufrufe.
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#emailModal">
                            <i class="fas fa-envelope"></i> Per E-Mail senden
                        </button>
                        <a href="{{ route('oauth.index') }}" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Neuen Flow starten
                        </a>
                        <div class="btn-group" role="group">
                            <a href="/oauth/force-logout" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-sync-alt"></i> Session Reset
                            </a>
                            <a href="/oauth/logout" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt"></i> OAuth Logout
                            </a>
                        </div>
                        <small class="text-muted text-center">
                            <i class="fas fa-info-circle"></i>
                            OAuth Logout erzwingt neue Anmeldung bei Passolution
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Modal -->
    <div class="modal fade email-modal" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">
                        <i class="fas fa-envelope"></i> Tokens per E-Mail senden
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="emailForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail-Adresse:</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="empfaenger@example.com">
                        </div>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i>
                            Die Tokens werden sicher formatiert und an die angegebene E-Mail-Adresse gesendet.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-success" onclick="sendEmail()">
                        <i class="fas fa-paper-plane"></i> E-Mail senden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.innerText;
            
            navigator.clipboard.writeText(text).then(function() {
                // Visual feedback
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                btn.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
                }, 2000);
            }).catch(function(err) {
                console.error('Fehler beim Kopieren: ', err);
                alert('Fehler beim Kopieren in die Zwischenablage');
            });
        }

        function sendEmail() {
            const email = document.getElementById('email').value;
            if (!email) {
                alert('Bitte geben Sie eine E-Mail-Adresse ein.');
                return;
            }

            const tokenData = @json($tokenData);
            const clientId = @json($clientId);
            const timestamp = @json($timestamp);

            const formData = new FormData();
            formData.append('email', email);
            formData.append('token_data', JSON.stringify(tokenData));
            formData.append('client_id', clientId);
            formData.append('timestamp', timestamp);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("oauth.send-tokens") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('E-Mail erfolgreich gesendet an: ' + email);
                    bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide();
                } else {
                    alert('Fehler beim Senden der E-Mail: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten.');
            });
        }
    </script>
</body>
</html>