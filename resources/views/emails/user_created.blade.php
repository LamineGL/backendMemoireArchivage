<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue</title>
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
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .credentials {
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
        }
        .credentials strong {
            color: #4CAF50;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .info-box {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎉 Bienvenue sur la plateforme</h1>
    </div>

    <p>Bonjour <strong>{{ $user->nom_complet }}</strong>,</p>

    <p>Votre compte a été créé avec succès sur la plateforme d'archivage Olam Agri.</p>

    <div class="credentials">
        <h3 style="margin-top: 0;">📧 Vos identifiants de connexion :</h3>
        <p>
            <strong>Email :</strong> {{ $user->email }}<br>
            <strong>Mot de passe :</strong> <code style="background: #fff; padding: 3px 8px; border-radius: 3px;">{{ $password }}</code>
        </p>
    </div>

    <div class="info-box">
        <p style="margin: 0;">
            <strong>👤 Rôle :</strong> {{ $user->role->nom_role ?? 'Non défini' }}<br>
            @if($user->departement)
                <strong>🏢 Département :</strong> {{ $user->departement->nom ?? $user->departement->nom_departement ?? 'Non défini' }}
            @else
                <strong>🏢 Département :</strong> Non défini
            @endif
        </p>
    </div>

    <p style="text-align: center;">
        <a href="{{ $loginUrl ?? '#' }}" class="button">
            Se connecter maintenant
        </a>
    </p>



    <p>Si vous rencontrez des difficultés pour vous connecter, n'hésitez pas à contacter l'administrateur.</p>

    <p>Cordialement,<br>
        <strong>L'équipe  IT Olam Agri</strong></p>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} Olam Agri. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
