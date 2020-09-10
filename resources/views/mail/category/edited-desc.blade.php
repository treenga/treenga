@component('mail::message')
# Category description edited

{{ $username }} edited description of <a href="{{ $link }}">{{ $category->name }}</a>.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
