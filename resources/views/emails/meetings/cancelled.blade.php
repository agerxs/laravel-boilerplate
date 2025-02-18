@component('mail::message')
# Réunion annulée

La réunion "{{ $meeting->title }}" a été annulée.

**Détails de la réunion :**
- Date : {{ $meeting->start_datetime->format('d/m/Y H:i') }}
- Lieu : {{ $meeting->location }}

@component('mail::button', ['url' => route('meetings.show', $meeting)])
Voir la réunion
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent 