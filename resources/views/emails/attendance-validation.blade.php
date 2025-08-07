<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validation des présences</title>
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
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Validation des présences</h2>
        <p>Bonjour {{ $prefet->name }},</p>
    </div>

    <div class="content">
        <p>Les présences de la réunion suivante ont été soumises et nécessitent votre validation :</p>

        <h3>Détails de la réunion</h3>
        <ul>
            <li><strong>Titre :</strong> {{ $meeting->title }}</li>
            <li><strong>Date :</strong> {{ \Carbon\Carbon::parse($meeting->scheduled_date)->format('d/m/Y') }}</li>
            <li><strong>Localité :</strong> {{ $meeting->localCommittee->locality->name }}</li>
            <li><strong>Comité local :</strong> {{ $meeting->localCommittee->name }}</li>
        </ul>

        <p>Pour valider les présences, veuillez cliquer sur le bouton ci-dessous :</p>

        <a href="{{ $validationUrl }}" class="button">
            Valider les présences
        </a>

        <div class="warning">
            <strong>⚠️ Important :</strong>
            <ul>
                <li>Ce lien expire le {{ $expiresAt }}</li>
                <li>Ce lien ne peut être utilisé qu'une seule fois</li>
                <li>La validation est définitive et ne peut pas être annulée</li>
            </ul>
        </div>

        <p>Si vous ne pouvez pas valider les présences via ce lien, vous pouvez également vous connecter à l'application et valider directement depuis l'interface.</p>
    </div>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement par le système de gestion des réunions.</p>
        <p>Si vous avez des questions, veuillez contacter l'administrateur du système.</p>
    </div>
</body>
</html> 