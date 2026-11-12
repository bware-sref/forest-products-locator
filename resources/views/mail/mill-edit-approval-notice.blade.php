<x-mail::message>
# Good news!

{{ $subject }}

@if($url)
<x-mail::button :url="$url">
View the listing for {{ $mill_name }}
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
