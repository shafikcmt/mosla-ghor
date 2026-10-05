{{-- MoslaMart notification email (Bangla greeting, sign-off and link help). --}}
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@elseif ($level === 'error')
# দুঃখিত!
@else
# আসসালামু আলাইকুম!
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
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

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
<p class="signoff">
@if (! empty($salutation))
{{ $salutation }}
@else
ধন্যবাদ,<br>
<strong>{{ \App\Models\WebsiteSetting::siteName() }} টিম</strong>
@endif
</p>

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
“{{ $actionText }}” বাটন কাজ না করলে নিচের লিংকটি কপি করে ব্রাউজারে খুলুন:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
