@component('mail::message')
# Invitation

{{ $username }} would like to invite you to register on {{ config('app.name') }} for collaboration.

@component('mail::button', ['url' => $link])
Continue registration
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent