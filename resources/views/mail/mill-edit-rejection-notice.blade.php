<x-mail::message>
# We're sorry

{{ $subject }}

@if ($reason)
Your submission was rejected for the reason(s) below.
<x-mail::panel>
    {{ $reason }}
</x-mail::panel>
@endif

If you believe your submission was rejected in error, please contact your state forestry service or the site administrators.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
