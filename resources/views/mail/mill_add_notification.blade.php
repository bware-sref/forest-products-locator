<x-mail::message>
# New Mill submitted

**Mill Name:** {{ $mill_name }}

**Submitted By:** {{ $email }}

**Submitted From:** {{ $ip }}

**Submitted At:** {{ $created_at }}

<x-mail::button :url="$url" color="primary">
Review New Mill
</x-mail::button>

</x-mail::message>