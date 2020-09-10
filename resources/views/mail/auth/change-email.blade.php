@component('mail::message')
# New email confirmation

@component('mail::button', ['url' => $link])
Confirm your new email
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
