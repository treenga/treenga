@component('mail::message')
# Unassigned from task

{{ $username }} unassigned you from <a href="{{ $task->url }}">{{ $task->name }}</a>.

You are receiving this because {{ $task->subscribe_type_email_text }}.<br>
<a href="{{ $task->unsubscribe_email_url }}">Mute the thread</a>.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
