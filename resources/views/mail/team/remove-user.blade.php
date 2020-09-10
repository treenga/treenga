@component('mail::message')
# Team access closed

{{ $username }} closed your access to "{{ $team->name }}" team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
