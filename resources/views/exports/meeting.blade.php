<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .agenda-item { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $meeting->title }}</h1>
        <p>Date : {{ $meeting->start_datetime->format('d/m/Y H:i') }}</p>
        <p>Lieu : {{ $meeting->location }}</p>
    </div>

    <div class="section">
        <h2>Ordre du jour</h2>
        @foreach($meeting->agenda as $item)
            <div class="agenda-item">
                <h3>{{ $item->title }}</h3>
                <p>{{ $item->description }}</p>
                <p>Durée : {{ $item->duration_minutes }} minutes</p>
                <p>Présentateur : {{ $item->presenter->name ?? 'Non assigné' }}</p>
            </div>
        @endforeach
    </div>

    @if($meeting->minutes)
        <div class="section">
            <h2>Compte rendu</h2>
            {!! $meeting->minutes->content !!}
        </div>
    @endif
</body>
</html> 