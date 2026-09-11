<x-mail::message>

# New Mill Update submitted

**Mill Name:**<br />
{{ $mill_name }}

<x-mail::button :url="$url" color="success">
Approve
</x-mail::button>

<x-mail::button :url="$url" color="error">
Reject
</x-mail::button>

**Submitted By:** {{ $email }}

**Submitted From:** {{ $ip }}

**Submitted At:** {{ $created_at }}

**Now:** {{ $now }}

{{ config('app.name') }}
</x-mail::message>