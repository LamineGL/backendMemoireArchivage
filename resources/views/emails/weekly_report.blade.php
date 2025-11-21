<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Hebdomadaire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #f5576c;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .section h3 {
            color: #f5576c;
            margin-top: 0;
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
    <h1>📊 Rapport Hebdomadaire</h1>
    <p>Période : {{ $stats['periode']['debut'] ?? '' }} - {{ $stats['periode']['fin'] ?? '' }}</p>
</div>

<div class="content">
    <p>Bonjour <strong>{{ $user->nom_complet }}</strong>,</p>

    <p>Voici le résumé de l'activité de la semaine dernière sur la plateforme d'archivage.</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $stats['documents_ajoutes'] ?? 0 }}</div>
            <div class="stat-label">Documents ajoutés</div>
        </div>

        <div class="stat-card">
            <div class="stat-number">{{ $stats['documents_modifies'] ?? 0 }}</div>
            <div class="stat-label">Documents modifiés</div>
        </div>

        <div class="stat-card">
            <div class="stat-number">{{ $stats['telechargements'] ?? 0 }}</div>
            <div class="stat-label">Téléchargements</div>
        </div>

        <div class="stat-card">
            <div class="stat-number">{{ $stats['utilisateurs_actifs'] ?? 0 }}</div>
            <div class="stat-label">Utilisateurs actifs</div>
        </div>
    </div>

    <div class="section">
        <h3>📈 Activité de la semaine</h3>
        <p>La plateforme a enregistré une activité constante cette semaine avec
            {{ $stats['documents_ajoutes'] ?? 0 }} nouveaux documents ajoutés et
            {{ $stats['utilisateurs_actifs'] ?? 0 }} utilisateurs actifs.</p>
    </div>

    <p>Pour plus de détails, consultez le tableau de bord administrateur.</p>

    <p>Cordialement,<br>
        <strong>Système d'Archivage Olam Agri</strong></p>
</div>

<div class="footer">
    <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} Olam Agri Sénégal - Système d'Archivage Documentaire</p>
</div>
</body>
</html>
