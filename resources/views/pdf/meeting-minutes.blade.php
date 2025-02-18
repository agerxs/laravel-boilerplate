<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.6;
            color: #2D3748;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: #F7FAFC;
            border-bottom: 2px solid #4F46E5;
        }

        .header h1 {
            color: #1A202C;
            margin: 0;
            font-size: 20px;
        }

        .header p {
            color: #4A5568;
            margin: 10px 0 0;
            font-size: 12px;
        }

        .section {
            margin-bottom: 25px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #4F46E5;
            font-size: 14px;
            margin-top: 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #E2E8F0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }

        .info-label {
            font-weight: bold;
            color: #4A5568;
        }

        .info-value {
            color: #2D3748;
        }

        .participants-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .participants-list li {
            padding: 8px 0;
            border-bottom: 1px solid #EDF2F7;
        }

        .participants-list li:last-child {
            border-bottom: none;
        }

        .agenda-item {
            background: #F7FAFC;
            padding: 12px;
            margin-bottom: 12px;
            border-left: 3px solid #4F46E5;
        }

        .agenda-item h3 {
            color: #1A202C;
            margin: 0 0 8px 0;
            font-size: 12px;
        }

        .agenda-item p {
            margin: 4px 0;
            color: #4A5568;
            font-size: 11px;
        }

        .agenda-item .duration {
            color: #718096;
            font-style: italic;
            font-size: 10px;
        }

        .minutes-content {
            line-height: 1.6;
            color: #2D3748;
            font-size: 11px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #E2E8F0;
            color: #718096;
            font-size: 10px;
        }

        .footer p {
            margin: 5px 0;
        }

        .page-number {
            position: running(pageNumber);
            font-size: 10px;
            color: #A0AEC0;
        }

        @page {
            margin: 80px 40px;
            @bottom-right {
                content: "Page " counter(page) " sur " counter(pages);
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $meeting->title }}</h1>
        <p>Compte rendu de réunion</p>
    </div>

    <div class="section">
        <h2>Informations générales</h2>
        <div class="info-grid">
            <div class="info-label">Date :</div>
            <div class="info-value">{{ $meeting->start_datetime->format('d/m/Y H:i') }}</div>
            
            <div class="info-label">Lieu :</div>
            <div class="info-value">{{ $meeting->location }}</div>
            
            <div class="info-label">Durée :</div>
            <div class="info-value">
                {{ $meeting->start_datetime->diffForHumans($meeting->end_datetime, ['parts' => 2]) }}
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Participants</h2>
        <ul class="participants-list">
            @foreach($meeting->participants as $participant)
                <li>
                    {{ $participant->user ? $participant->user->name : $participant->guest_name }}
                    @if($participant->user && $participant->user->email)
                        <span style="color: #718096"> - {{ $participant->user->email }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    @if($meeting->agenda->count() > 0)
    <div class="section">
        <h2>Ordre du jour</h2>
        @foreach($meeting->agenda as $item)
            <div class="agenda-item">
                <h3>{{ $item->title }}</h3>
                @if($item->description)
                    <p>{{ $item->description }}</p>
                @endif
                <p class="duration">
                    Durée prévue : {{ $item->duration_minutes }} minutes
                    @if($item->presenter)
                        | Présentateur : {{ $item->presenter->name }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>
    @endif

    <div class="section">
        <h2>Compte rendu</h2>
        <div class="minutes-content">
            {!! $meeting->minutes->content !!}
        </div>
    </div>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>{{ config('app.name') }}</p>
    </div>
</body>
</html> 