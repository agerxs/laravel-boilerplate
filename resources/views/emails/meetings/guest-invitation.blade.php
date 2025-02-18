@component('mail::message')
# Invitation à une réunion

Bonjour {{ $guestName }},

Vous êtes invité(e) à participer à la réunion "{{ $meeting->title }}".

**Détails de la réunion :**
- Date : {{ $meeting->start_datetime->format('d/m/Y H:i') }}
- Lieu : {{ $meeting->location }}
- Description : {{ $meeting->description }}

@if($meeting->agenda->count() > 0)
**Ordre du jour :**
@foreach($meeting->agenda as $item)
- {{ $item->title }} ({{ $item->duration_minutes }} minutes)
@endforeach
@endif

@component('mail::button', ['url' => route('meetings.show', $meeting)])
Voir les détails de la réunion
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent 