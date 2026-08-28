<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@else
# {{ __('messages.mail.greeting') }}
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach

@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@if (! empty($salutation))
{{ $salutation }}
@else
{{ __('messages.mail.salutation') }} {{ config('app.name') }}
@endif

@isset($actionText)
<x-slot:subcopy>
{{ __('messages.mail.subcopy', ['action' => $actionText]) }}
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
