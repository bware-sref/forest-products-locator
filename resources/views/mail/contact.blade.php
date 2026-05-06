<x-mail::message>
# Markdown!

{{ $name . ' ' ?? '' }}<{{ $email }}> has a contact request from [{{ config('app.name') }}]({{ config('app.url') }}).
---

**Subject:**<br>
{{ $subject }}

**Message:**<br>
<x-mail::panel>
{{ $message }}
</x-mail::panel>

**IP Address:**<br>
{{ $ip }}

<x-mail::button :url="config('app.url')">
Allegedly Beautiful Button
</x-mail::button>

<x-mail::button :url="config('app.url')" color="primary">
Primary Button
</x-mail::button>

<x-mail::button :url="config('app.url')" color="success">
Success Button
</x-mail::button>

<x-mail::button :url="config('app.url')" color="error">
Error Button
</x-mail::button>

</x-mail::message>
