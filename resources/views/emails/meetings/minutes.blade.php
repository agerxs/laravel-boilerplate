@component('mail::message')
# Compte rendu de réunion

Le compte rendu de la réunion "{{ $meeting->title }}" est disponible.

**Informations générales :**
- Date : {{ $meeting->start_datetime ? $meeting->start_datetime->format('d/m/Y H:i') : 'Date non définie' }}
- Lieu : {{ $meeting->location ?? 'Lieu non défini' }}

**Participants :**
@if(isset($meeting->attendees) && $meeting->attendees->count() > 0)
@foreach($meeting->attendees as $attendee)
- {{ $attendee->user ? $attendee->user->name : $attendee->guest_name }}
@endforeach
@else
- Aucun participant enregistré
@endif

@if(isset($meeting->agenda) && $meeting->agenda->count() > 0)
**Ordre du jour :**
@foreach($meeting->agenda as $item)
- {{ $item->title }} ({{ $item->duration_minutes }} minutes)
@endforeach
@else
**Ordre du jour :**
- Aucun ordre du jour défini
@endif

**Compte rendu :**
{!! $meeting->minutes->content !!}

**Pièces jointes :**
- compte-rendu-{{ $meeting->id }}.pdf (Compte rendu au format PDF)
@foreach($meeting->attachments as $attachment)
- {{ $attachment->name }}
@endforeach

@component('mail::button', ['url' => route('meetings.show', $meeting)])
Voir la réunion
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent 