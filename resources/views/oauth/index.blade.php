<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Token Generator - Passolution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .header-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 3rem;
        }
        .info-box {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center mb-4">
                    <i class="fas fa-key header-icon"></i>
                    <h1 class="text-white mt-3 mb-2">OAuth2 Token Generator</h1>
                    <p class="text-white-50">Generieren Sie OAuth2 Tokens für Passolution API</p>
                </div>

                <div class="card">
                    <div class="card-body p-4">
                        <div class="info-box">
                            <h6 class="mb-2"><i class="fas fa-info-circle text-primary"></i> Information</h6>
                            <p class="mb-0 small">Geben Sie Ihre Client-Daten ein, um OAuth2 Tokens zu generieren. Die Daten werden nur für diese Session verwendet und nicht gespeichert.</p>
                        </div>

                        <form action="{{ route('oauth.authorize') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="client_id" class="form-label fw-semibold">
                                    <i class="fas fa-id-card text-primary me-2"></i>Client ID
                                </label>
                                <input type="text" 
                                       class="form-control @error('client_id') is-invalid @enderror" 
                                       id="client_id" 
                                       name="client_id" 
                                       value="{{ old('client_id') }}"
                                       placeholder="Ihre Client ID eingeben"
                                       required>
                                @error('client_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="client_secret" class="form-label fw-semibold">
                                    <i class="fas fa-lock text-primary me-2"></i>Client Secret
                                </label>
                                <input type="password" 
                                       class="form-control @error('client_secret') is-invalid @enderror" 
                                       id="client_secret" 
                                       name="client_secret"
                                       placeholder="Ihr Client Secret eingeben"
                                       required>
                                @error('client_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>
                                    OAuth2 Flow starten
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-muted mb-2">Konfigurierte URLs:</h6>
                            <div class="small text-muted">
                                <div><strong>Auth URL:</strong> https://web.passolution.eu/en/oauth/authorize</div>
                                <div><strong>Token URL:</strong> https://web.passolution.eu/en/oauth/token</div>
                                <div><strong>Redirect URI:</strong> https://api-client-oauth2-v3.passolution.de/oauth/callback</div>
                                <div><strong>API Base:</strong> https://api.passolution.eu/api/v2</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mt-3">
                        <h6><i class="fas fa-exclamation-triangle"></i> Fehler</h6>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>