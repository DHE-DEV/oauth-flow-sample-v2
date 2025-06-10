<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Tokens</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            color: #333;
            border-left: 4px solid #007bff;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        .token-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table th,
        .info-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        .info-table th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 OAuth2 Tokens</h1>
            <p>Ihre OAuth2-Authentifizierungsdaten</p>
        </div>

        <div class="section">
            <h2>📋 Client-Informationen</h2>
            <table class="info-table">
                <tr>
                    <th>Client ID</th>
                    <td>{{ $clientId }}</td>
                </tr>
                <tr>
                    <th>Redirect URI</th>
                    <td>{{ $redirectUri }}</td>
                </tr>
                <tr>
                    <th>Generiert am</th>
                    <td>{{ $generatedAt }}</td>
                </tr>
            </table>
        </div>

        @if(isset($tokens['access_token']))
        <div class="section">
            <h2>🎫 Access Token</h2>
            <div class="token-box">
                {{ $tokens['access_token'] }}
            </div>
            
            @if(isset($tokens['token_type']) || isset($tokens['expires_in']) || isset($tokens['scope']))
            <table class="info-table">
                @if(isset($tokens['token_type']))
                <tr>
                    <th>Token Type</th>
                    <td>{{ $tokens['token_type'] }}</td>
                </tr>
                @endif
                @if(isset($tokens['expires_in']))
                <tr>
                    <th>Gültigkeitsdauer</th>
                    <td>{{ $tokens['expires_in'] }} Sekunden</td>
                </tr>
                @endif
                @if(isset($tokens['scope']))
                <tr>
                    <th>Scope</th>
                    <td>{{ $tokens['scope'] }}</td>
                </tr>
                @endif
            </table>
            @endif
        </div>
        @endif

        @if(isset($tokens['refresh_token']))
        <div class="section">
            <h2>🔄 Refresh Token</h2>
            <div class="token-box">
                {{ $tokens['refresh_token'] }}
            </div>
        </div>
        @endif

        <div class="warning">
            <strong>⚠️ Wichtiger Sicherheitshinweis:</strong><br>
            Diese Tokens gewähren Zugriff auf Ihre Anwendung. Behandeln Sie sie vertraulich und teilen Sie sie niemals mit Unbefugten. 
            Die Tokens sind nur für die aktuelle Session gültig und werden nicht dauerhaft gespeichert.
        </div>

        <div class="section">
            <h2>🔧 Verwendung</h2>
            <p><strong>Authorization Header:</strong></p>
            <div class="token-box">
                Authorization: {{ $tokens['token_type'] ?? 'Bearer' }} {{ $tokens['access_token'] ?? 'YOUR_ACCESS_TOKEN' }}
            </div>
            <p>Verwenden Sie diesen Header bei API-Anfragen zur Authentifizierung.</p>
        </div>

        <div class="footer">
            <p>Diese E-Mail wurde automatisch vom OAuth2 Flow Visualizer generiert.</p>
            <p>Generiert am {{ $generatedAt }}</p>
        </div>
    </div>
</body>
</html>