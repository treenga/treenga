@component('mail::message')
# Team invitation

{{ $username }} invited you to "{{ $team->name }}" team.

@component('mail::button', ['url' => $team->url])
Open team
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
