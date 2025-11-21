<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouveau Document</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-left: 4px solid #667eea;
        }
        .document-info p {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #667eea;
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
    <h1>📄 Nouveau Document Ajouté</h1>
</div>

<div class="content">
    <p>Bonjour <strong>{{ $user->nom_complet }}</strong>,</p>

    <p>Un nouveau document vient d'être ajouté dans votre département.</p>

    <div class="document-info">
        <p><span class="label">Titre :</span> {{ $document->titre }}</p>
        <p><span class="label">Type :</span> {{ $document->typeDocument->libelle_type ?? 'N/A' }}</p>
        <p><span class="label">Département :</span> {{ $document->departement->nom_departement }}</p>
        <p><span class="label">Ajouté par :</span> {{ $document->createur->nom_complet ?? 'N/A' }}</p>
        <p><span class="label">Date :</span> {{ $document->created_at->format('d/m/Y à H:i') }}</p>
        @if($document->description)
            <p><span class="label">Description :</span> {{ $document->description }}</p>
        @endif
    </div>

    <p>Connectez-vous à la plateforme pour consulter ce document.</p>

    <p>Cordialement,<br>
        <strong>Système d'Archivage Olam Agri</strong></p>
</div>

<div class="footer">
    <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} Olam Agri Sénégal - Système d'Archivage Documentaire</p>
</div>
</body>
</html>
