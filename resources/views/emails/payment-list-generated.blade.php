<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle liste de paiement générée</title>
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
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Nouvelle liste de paiement générée</h1>
    </div>
    
    <div class="content">
        <p>Bonjour <strong>{{ $notifiable->name }}</strong>,</p>
        
        <p>Une nouvelle liste de paiement a été générée et nécessite votre attention.</p>
        
        <div class="details">
            <h3>📋 Détails de la réunion</h3>
            <ul>
                <li><strong>Comité :</strong> {{ $meeting->localCommittee?->name ?? 'Comité Local' }}</li>
                <li><strong>Réunion :</strong> {{ $meeting->title }}</li>
                <li><strong>Date :</strong> {{ $meeting->scheduled_date?->format('d/m/Y à H:i') ?? 'Date non définie' }}</li>
                <li><strong>Lieu :</strong> {{ $meeting->location ?? 'Non spécifié' }}</li>
                <li><strong>Nombre de représentants :</strong> {{ $representativesCount }}</li>
                <li><strong>Montant total :</strong> {{ number_format($representativesCount * 15000, 0, ',', ' ') }} FCFA</li>
                <li><strong>Généré par :</strong> {{ $submittedBy->name }}</li>
                <li><strong>Fichier :</strong> {{ $fileName }}</li>
            </ul>
        </div>
        
        <div class="highlight">
            <p><strong>⚠️ Action requise :</strong> Veuillez vous connecter à l'application pour traiter cette liste de paiement.</p>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ url('/login') }}" class="button">
                🔗 Se connecter à l'application
            </a>
        </div>
        
        <p>Merci de votre attention.</p>
        
        <p>Cordialement,<br>
        <strong>Équipe COLOCS</strong></p>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement par le système COLOCS.</p>
        <p>Si vous avez des questions, veuillez contacter l'équipe de support.</p>
    </div>
</body>
</html> 