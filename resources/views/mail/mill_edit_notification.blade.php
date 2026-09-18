<x-mail::message>
# Mill Update submitted

**Mill Name:** {{ $mill_name }}

**Submitted By:** {{ $email }}

**Submitted From:** {{ $ip }}

**Submitted At:** {{ $created_at }}

<x-mail::button :url="$url" color="primary">
Review Edits
</x-mail::button>

</x-mail::message>