<x-mail::message>

# New Mill Update submitted

**Mill Name:**<br />
{{ $mill_name }}

<x-mail::button :url="'#'">
    Approve
</x-mail::button>

<x-mail::button :url="''">
    Reject
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>