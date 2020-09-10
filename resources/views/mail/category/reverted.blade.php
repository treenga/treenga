@component('mail::message')
# Category description reverted

{{ $username }} reverted description of <a href="{{ $link }}">{{ $category->name }}</a> to previous version.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
