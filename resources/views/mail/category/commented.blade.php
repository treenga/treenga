@component('mail::message')
# New comment

{{ $username }} commented description of <a href="{{ $link }}">{{ $category->name }}</a>.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
