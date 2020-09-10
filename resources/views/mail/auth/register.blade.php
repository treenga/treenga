@component('mail::message')
# Welcome to {{ config('app.name') }}!

@component('mail::button', ['url' => $link])
Confirm your email
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
