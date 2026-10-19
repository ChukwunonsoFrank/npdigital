<x-mail::message :title="$subject">

# {{ $greeting }}

@foreach ($introLines as $line)
{{ $line }}

@endforeach

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

Thanks,<br>
The NP Digital Team

<x-slot:subcopy>
If you're having trouble clicking the "{{ $actionText }}" button, copy and paste the URL below into your web browser:
<span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
</x-mail::message>
