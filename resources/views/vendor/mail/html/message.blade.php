@props(['title' => 'Your NP Digital account'])
<x-mail::layout :title="$title">
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
NP Digital
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
    {{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} NP Digital. {{ __('All rights reserved.') }}
<br>
<a href="{{ config('app.url') }}">Website</a> &nbsp;&middot;&nbsp; <a href="mailto:{{ config('mail.support_address') }}">Contact support</a>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
