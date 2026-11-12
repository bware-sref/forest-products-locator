<x-mail::message>
# Good news!

{{ $subject }}

@isset($url)
<x-mail::button :url="$url">
View the listing for {{ $mill_name }}
</x-mail::button>
@endisset

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
