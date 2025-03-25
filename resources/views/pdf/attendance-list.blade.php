<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste de présence - {{ $meeting->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.5;
            color: #333;
        }
        h1 {
            text-align: center;
            font-size: 20px;
            color: #000;
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .meeting-info {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .meeting-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .present {
            background-color: #d4edda;
        }
        .absent {
            background-color: #f8d7da;
        }
        .replaced {
            background-color: #fff3cd;
        }
        .signature-area {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            border-top: 1px solid #000;
            padding-top: 10px;
            width: 200px;
            text-align: center;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LISTE DE PRÉSENCE</h1>
    </div>

    <div class="meeting-info">
        <p><strong>Réunion :</strong> {{ $meeting->title }}</p>
        <p><strong>Date :</strong> {{ $meeting->scheduled_date->format('d/m/Y H:i') }}</p>
        <p><strong>Lieu :</strong> {{ $meeting->location }}</p>
        <p><strong>Comité local :</strong> {{ $meeting->localCommittee->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 20%;">Nom</th>
                <th style="width: 15%;">Village</th>
                <th style="width: 15%;">Rôle</th>
                <th style="width: 10%;">Statut</th>
                <th style="width: 15%;">Remplaçant</th>
                <th style="width: 20%;">Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendees as $index => $attendee)
                <tr class="{{ $attendee->attendance_status === 'present' ? 'present' : ($attendee->attendance_status === 'absent' ? 'absent' : ($attendee->attendance_status === 'replaced' ? 'replaced' : '')) }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $attendee->name }}</td>
                    <td>{{ $attendee->village ? $attendee->village->name : '-' }}</td>
                    <td>{{ $attendee->role ?? '-' }}</td>
                    <td>
                        @if($attendee->attendance_status === 'present')
                            Présent
                        @elseif($attendee->attendance_status === 'absent')
                            Absent
                        @elseif($attendee->attendance_status === 'replaced')
                            Remplacé
                        @else
                            Prévu
                        @endif
                    </td>
                    <td>{{ $attendee->replacement_name ?? '-' }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        <p><strong>Nombre de participants prévus :</strong> {{ $attendees->count() }}</p>
        <p><strong>Nombre de présents :</strong> {{ $attendees->where('attendance_status', 'present')->count() + $attendees->where('attendance_status', 'replaced')->count() }}</p>
        <p><strong>Nombre d'absents :</strong> {{ $attendees->where('attendance_status', 'absent')->count() }}</p>
    </div>

    <div class="signature-area">
        <div class="signature-box">
            Signature du Président
        </div>
        <div class="signature-box">
            Signature du Secrétaire
        </div>
    </div>

    <div class="footer">
        <p>Document généré le {{ $generated_at }}</p>
    </div>
</body>
</html> 