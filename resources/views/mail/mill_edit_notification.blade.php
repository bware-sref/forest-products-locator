<x-mail::message>

# New Mill Update submitted

**Mill Name:** {{ $mill_name }}

**Submitted By:** {{ $email }}

**Submitted From:** {{ $ip }}

**Submitted At:** {{ $created_at }}

<x-mail::button :url="$newUrl" color="primary">
Review Edits
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>