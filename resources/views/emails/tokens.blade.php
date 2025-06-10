<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Tokens - Passolution API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 28px;
        }
        .info-section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .info-section h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }
        .token-container {
            background-color: #f1f3f4;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #e1e5e9;
        }
        .token-label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .token-value {
            font-family: 'Courier New', monospace;
            background-color: white;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            word-break: break-all;
            font-size: 12px;
            color: #333;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning h4 {
            color: #856404;
            margin-top: 0;
        }
        .warning p {
            color: #856404;
            margin-bottom: 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .expires-info {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 13px;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 OAuth2 Access Tokens</h1>
            <p>Passolution API Zugriffsdaten</p>
        </div>

        <div class="info-section">
            <h3>📋 Client Information</h3>
            <p><strong>Client ID:</strong> {{ $clientId }}</p>
            <p><strong>Erstellt am:</strong> {{ $timestamp }}</p>
        </div>

        <div class="info-section">
            <h3>🎯 Access Token</h3>
            <div class="token-container">
                <div class="token-label">Access Token:</div>
                <div class="token-value">{{ $tokenData['access_token'] }}</div>
            </div>
            @if(isset($tokenData['expires_in']))
            <div class="expires-info">
                <strong>⏰ Gültigkeitsdauer:</strong> {{ $tokenData['expires_in'] }} Sekunden
                <br><strong>Läuft ab am:</strong> {{ date('Y-m-d H:i:s', time() + $tokenData['expires_in']) }}
            </div>
            @endif
        </div>

        @if(isset($tokenData['refresh_token']))
        <div class="info-section">
            <h3>🔄 Refresh Token</h3>
            <div class="token-container">
                <div class="token-label">Refresh Token:</div>
                <div class="token-value">{{ $tokenData['refresh_token'] }}</div>
            </div>
        </div>
        @endif

        @if(isset($tokenData['scope']))
        <div class="info-section">
            <h3>🎯 Scope</h3>
            <p><code>{{ $tokenData['scope'] ?: 'Keine spezifischen Scopes' }}</code></p>
        </div>
        @endif

        @if(isset($tokenData['token_type']))
        <div class="info-section">
            <h3>🏷️ Token Type</h3>
            <p><code>{{ $tokenData['token_type'] }}</code></p>
        </div>
        @endif

        <div class="warning">
            <h4>⚠️ Wichtige Sicherheitshinweise</h4>
            <p>• Diese Tokens gewähren vollen Zugriff auf die Passolution API</p>
            <p>• Bewahren Sie diese Informationen sicher auf</p>
            <p>• Teilen Sie diese Tokens niemals öffentlich</p>
            <p>• Verwenden Sie HTTPS für alle API-Anfragen</p>
            <p>• Erneuern Sie Tokens regelmäßig mit dem Refresh Token</p>
        </div>

        <div class="info-section">
            <h3>🔗 API Verwendung</h3>
            <p><strong>Base URL:</strong> <code>https://api.passolution.eu/api/v2</code></p>
            <p><strong>Authorization Header:</strong></p>
            <div class="token-container">
                <div class="token-value">Authorization: Bearer {{ $tokenData['access_token'] }}</div>
            </div>
        </div>

        <div class="footer">
            <p>Diese E-Mail wurde automatisch generiert.<br>
            Bei Fragen wenden Sie sich an den Support.</p>
            <p><strong>Passolution API OAuth2 Service</strong></p>
        </div>
    </div>
</body>
</html>