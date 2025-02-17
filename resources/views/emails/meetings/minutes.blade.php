@component('mail::message')
# Compte rendu de réunion

Le compte rendu de la réunion "{{ $meeting->title }}" est disponible.

**Informations générales :**
- Date : {{ $meeting->start_datetime->format('d/m/Y H:i') }}
- Lieu : {{ $meeting->location }}

**Participants :**
@foreach($meeting->participants as $participant)
- {{ $participant->user ? $participant->user->name : $participant->guest_name }}
@endforeach

@if($meeting->agenda->count() > 0)
**Ordre du jour :**
@foreach($meeting->agenda as $item)
- {{ $item->title }} ({{ $item->duration_minutes }} minutes)
@endforeach
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