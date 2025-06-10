<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Fehler - Passolution</title>
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
        .error-icon {
            color: #dc3545;
            font-size: 4rem;
        }
        .error-code {
            font-family: 'Courier New', monospace;
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 5px;
            padding: 15px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle error-icon"></i>
                    <h1 class="text-white mt-3 mb-2">OAuth2 Fehler</h1>
                    <p class="text-white-50">Ein Fehler ist während des OAuth2-Prozesses aufgetreten</p>
                </div>

                <div class="card">
                    <div class="card-body p-4 text-center">
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">
                                <i class="fas fa-times-circle me-2"></i>Fehler aufgetreten
                            </h5>
                            <p class="mb-0">Der OAuth2-Prozess konnte nicht abgeschlossen werden.</p>
                        </div>

                        @if(isset($error))
                        <div class="mt-4">
                            <h6 class="text-muted mb-2">Fehlermeldung:</h6>
                            <div class="error-code">
                                {{ $error }}
                            </div>
                        </div>
                        @endif

                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Mögliche Ursachen:</h6>
                            <ul class="text-start text-muted">
                                <li>Ungültige Client-Daten (Client ID oder Secret)</li>
                                <li>Benutzer hat die Autorisierung verweigert</li>
                                <li>Netzwerkfehler oder Server nicht erreichbar</li>
                                <li>Ungültige oder abgelaufene Autorisierungsanfrage</li>
                            </ul>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('oauth.index') }}" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Erneut versuchen
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-white-50 small">
                        Bei weiteren Problemen wenden Sie sich bitte an den Support.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>