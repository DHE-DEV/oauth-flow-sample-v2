<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Flow Demo - Passolution API</title>
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
        .step-indicator {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .step-title {
            color: #667eea;
            font-weight: 600;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .info-card {
            background: linear-gradient(135deg, #f8f9ff, #e8f0ff);
            border: 1px solid #e1e8ff;
            border-radius: 10px;
        }
        .flow-step {
            border-left: 3px solid #667eea;
            padding-left: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="main-container p-4 p-md-5">
            <div class="text-center mb-5">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-shield-alt text-primary"></i>
                    OAuth2 Flow Demo
                </h1>
                <p class="lead text-muted">Passolution API Authorization</p>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card h-100 border-0 info-card">
                        <div class="card-body">
                            <h4 class="card-title text-primary mb-4">
                                <i class="fas fa-info-circle"></i> OAuth2 Flow Schritte
                            </h4>
                            
                            <div class="flow-step">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="step-indicator me-3">1</div>
                                    <h6 class="step-title mb-0">Client Credentials eingeben</h6>
                                </div>
                                <p class="text-muted small">Geben Sie Ihre Client ID und Client Secret ein.</p>
                            </div>

                            <div class="flow-step">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="step-indicator me-3">2</div>
                                    <h6 class="step-title mb-0">Authorization Request</h6>
                                </div>
                                <p class="text-muted small">Weiterleitung zum Passolution Authorization Server.</p>
                            </div>

                            <div class="flow-step">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="step-indicator me-3">3</div>
                                    <h6 class="step-title mb-0">User Authorization</h6>
                                </div>
                                <p class="text-muted small">Benutzer meldet sich an und genehmigt die Zugriffe.</p>
                            </div>

                            <div class="flow-step">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="step-indicator me-3">4</div>
                                    <h6 class="step-title mb-0">Authorization Code</h6>
                                </div>
                                <p class="text-muted small">Rückleitung mit Authorization Code.</p>
                            </div>

                            <div class="flow-step">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="step-indicator me-3">5</div>
                                    <h6 class="step-title mb-0">Token Exchange</h6>
                                </div>
                                <p class="text-muted small">Austausch des Codes gegen Access Token.</p>
                            </div>

                            <div class="flow-step">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="step-indicator me-3">6</div>
                                    <h6 class="step-title mb-0">API Zugriff</h6>
                                </div>
                                <p class="text-muted small">Verwendung des Access Tokens für API-Aufrufe.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100 border-0">
                        <div class="card-body">
                            <h4 class="card-title text-primary mb-4">
                                <i class="fas fa-key"></i> Client Credentials
                            </h4>

                            <form action="{{ route('oauth.authorize') }}" method="POST">
                                @csrf
                                
                                <div class="mb-4">
                                    <label for="client_id" class="form-label fw-bold">
                                        <i class="fas fa-id-card"></i> Client ID
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="client_id" 
                                           name="client_id" 
                                           placeholder="Ihre Client ID eingeben"
                                           required>
                                    <div class="form-text">Die von Passolution bereitgestellte Client ID</div>
                                </div>

                                <div class="mb-4">
                                    <label for="client_secret" class="form-label fw-bold">
                                        <i class="fas fa-lock"></i> Client Secret
                                    </label>
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="client_secret" 
                                           name="client_secret" 
                                           placeholder="Ihr Client Secret eingeben"
                                           required>
                                    <div class="form-text">Das von Passolution bereitgestellte Client Secret</div>
                                </div>

                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Hinweis:</strong> Die Credentials werden nur für diese Session gespeichert und nach dem Token-Austausch automatisch gelöscht.
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-rocket"></i>
                                        OAuth2 Flow starten
                                    </button>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-outline-primary" formaction="/oauth/authorize-force">
                                        <i class="fas fa-shield-alt"></i>
                                        Mit erzwungener Re-Autorisierung starten
                                    </button>
                                </div>
                                <small class="text-muted text-center mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    "Erzwungene Re-Autorisierung" fordert Benutzer-Zustimmung erneut an
                                </small>
                            </form>

                            @if($errors->any())
                                <div class="alert alert-danger mt-3" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Fehler:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success mt-3" role="alert">
                                    <i class="fas fa-check-circle"></i>
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-outline-warning btn-sm w-100 mb-2" onclick="clearSession()">
                                            <i class="fas fa-sync-alt"></i> Session zurücksetzen
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="/oauth/logout" class="btn btn-outline-danger btn-sm w-100 mb-2">
                                            <i class="fas fa-sign-out-alt"></i> Passolution Logout
                                        </a>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Bei Login-Problemen: Logout verwenden oder Inkognito-Modus
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Sicherheitshinweis:</strong> 
                        Diese Demo-Anwendung dient nur zu Testzwecken. In einer Produktionsumgebung sollten Client Secrets niemals im Frontend verarbeitet oder angezeigt werden.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearSession() {
            // Browser Session Storage löschen
            sessionStorage.clear();
            localStorage.clear();
            
            // Cookies für diese Domain löschen
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            
            alert('Browser-Session wurde zurückgesetzt. Für einen kompletten Reset öffnen Sie die Seite im Inkognito-Modus.');
        }
    </script>
</body>
</html>