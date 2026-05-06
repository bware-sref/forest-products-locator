<x-mail::message>

{{ $name . ' ' ?? '' }}&lt;{{ $email }}&gt; has sent a contact request from [{{ config('app.name') }}]({{ config('app.url') }}).

---

**Subject:**<br>
{{ $subject }}

**Message:**<br>
<x-mail::panel>
{{ $message }}
</x-mail::panel>

**IP Address:**<br>
{{ $ip }}

</x-mail::message>
