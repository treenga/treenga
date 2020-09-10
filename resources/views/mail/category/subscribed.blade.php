@component('mail::message')
# You mentioned

{{ $username }} mentioned you in description of <a href="{{ $link }}">{{ $category->name }}</a>.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
