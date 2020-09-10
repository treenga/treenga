@component('mail::message')
# Reset password

@component('mail::button', ['url' => $link])
Set new password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
