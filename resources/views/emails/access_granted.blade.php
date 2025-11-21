<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Accès Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .document-info {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #11998e;
        }
        .document-info p {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #11998e;
        }
        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
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
    </style>
</head>
<body>
<div class="header">
    <h1>🔓 Accès à un Document</h1>
</div>

<div class="content">
    <p>Bonjour <strong>{{ $user->nom_complet }}</strong>,</p>

    <p>Vos permissions d'accès pour un document ont été modifiées.</p>

    <div class="document-info">
        <p><span class="label">Titre :</span> {{ $document->titre }}</p>
        <p><span class="label">Type :</span> {{ $document->typeDocument->libelle_type ?? 'N/A' }}</p>
        <p><span class="label">Département :</span> {{ $document->departement->nom_departement }}</p>
    </div>

    <div class="alert">
        ⚠️ <strong>Important :</strong> Connectez-vous pour voir vos permissions exactes sur ce document.
    </div>

    <p>Cordialement,<br>
        <strong>Système d'Archivage Olam Agri</strong></p>
</div>

<div class="footer">
    <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} Olam Agri Sénégal - Système d'Archivage Documentaire</p>
</div>
</body>
</html>
