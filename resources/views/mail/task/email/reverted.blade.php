@component('mail::message')
# Task reverted

{{ $username }} reverted <a href="{{ $task->url }}">{{ $task->name }}</a> to previous version.

You are receiving this because {{ $task->subscribe_type_email_text }}.<br>
<a href="{{ $task->unsubscribe_email_url }}">Mute the thread</a>.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
