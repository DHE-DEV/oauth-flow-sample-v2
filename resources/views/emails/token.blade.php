<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Tokens - Passolution</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .token-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .token-section h3 {
            color: #667eea;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .token-value {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            word-break: break-all;
            font-size: 12px;
            color: #333;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        @media (max-width: 600px) {
            .meta-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 OAuth2 Tokens</h1>
            <p>Passolution API Zugangsdaten</p>
        </div>

        <div class="info-section">
            <div class="meta-info">
                <strong>Client ID:</strong> {{ $clientId }}
            </div>
            <div class="meta-info">
                <strong>Generiert am:</strong> {{ $timestamp }}
            </div>
        </div>

        <div class="warning">
            <strong>⚠️ Sicherheitshinweis:</strong><br>
            Diese Tokens gewähren Zugriff auf Ihre API-Ressourcen. Teilen Sie diese Informationen nur mit vertrauenswürdigen Personen und verwahren Sie sie sicher.
        </div>

        <div class="token-section">
            <h3>🔑 Access Token</h3>
            <div class="token-value">{{ $tokenData['access_token'] ?? 'N/A' }}</div>
            @if(isset($tokenData['token_type']) || isset($tokenData['expires_in']))
            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                @if(isset($tokenData['token_type']))
                    <strong>Type:</strong> {{ $tokenData['token_type'] }}
                @endif
                @if(isset($tokenData['expires_in']))
                    | <strong>Läuft ab in:</strong> {{ $tokenData['expires_in'] }} Sekunden
                @endif
            </div>
            @endif
        </div>

        @if(isset($tokenData['refresh_token']))
        <div class="token-section">
            <h3>🔄 Refresh Token</h3>
            <div class="token-value">{{ $tokenData['refresh_token'] }}</div>
            <p style="margin-top: 10px; font-size: 12px; color: #666; margin-bottom: 0;">
                Verwenden Sie diesen Token, um neue Access Tokens zu erhalten, wenn der aktuelle abläuft.
            </p>
        </div>
        @endif

        @if(isset($tokenData['scope']))
        <div class="token-section">
            <h3>📝 Berechtigungen (Scope)</h3>
            <div class="token-value">{{ $tokenData['scope'] }}</div>
        </div>
        @endif

        <div class="token-section">
            <h3>📄 Vollständige Antwort (JSON)</h3>
            <div class="token-value">{{ json_encode($tokenData, JSON_PRETTY_PRINT) }}</div>
        </div>

        <div style="background: #e8f4ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4 style="color: #0066cc; margin-top: 0;">📚 API-Informationen</h4>
            <p style="margin-bottom: 5px;"><strong>Base URL:</strong> https://api.passolution.eu/api/v2</p>
            <p style="margin-bottom: 5px;"><strong>Authorization:</strong> Bearer [Access Token]</p>
            <p style="margin-bottom: 0;"><strong>Content-Type:</strong> application/json</p>
        </div>

        <div style="background: #fff2e6; padding: 15px; border-radius: 5px;">
            <h4 style="color: #cc6600; margin-top: 0;">💡 Verwendungshinweise</h4>
            <ul style="margin-bottom: 0; padding-left: 20px;">
                <li>Fügen Sie den Access Token in den Authorization-Header Ihrer API-Anfragen ein</li>
                <li>Verwenden Sie den Refresh Token, um neue Access Tokens zu erhalten</li>
                <li>Speichern Sie die Tokens sicher und teilen Sie sie nicht öffentlich</li>
                <li>Prüfen Sie regelmäßig die Gültigkeit der Tokens</li>
            </ul>
        </div>

        <div class="footer">
            <p>Diese E-Mail wurde automatisch generiert.<br>
            Bei Fragen wenden Sie sich bitte an den Support.</p>
            <p><strong>Passolution OAuth2 Service</strong></p>
        </div>
    </div>
</body>
</html>