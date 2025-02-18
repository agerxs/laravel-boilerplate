@component('mail::message')
# Ordre du jour mis à jour

L'ordre du jour de la réunion "{{ $meeting->title }}" a été mis à jour.

Date : {{ $meeting->start_datetime->format('d/m/Y H:i') }}
Lieu : {{ $meeting->location }}

@component('mail::button', ['url' => route('meetings.show', $meeting)])
Voir la réunion
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent 