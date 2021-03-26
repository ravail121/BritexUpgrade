@component('mail::layout')
{{-- Header --}}
@slot('header')
@isset($companyDetail)
@component('mail::header', ['url' => $companyDetail->url])
<img src="{{$companyDetail->email_header}}" />
@endcomponent
@endisset
@endslot
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level == 'error')
# Whoops!
@else
{{-- # Hello! --}}
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{!! $line !!}
@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
switch ($level) {
    case 'success':
        $color = 'green';
        break;
    case 'error':
        $color = 'red';
        break;
    default:
        $color = 'blue';
        break;
}
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}
@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
{{-- Regards,<br>{{ config('app.name') }} --}}
@endif

{{-- Subcopy --}}
@isset($actionText)
@component('mail::subcopy')
If you’re having trouble clicking the "{{ $actionText }}" button, copy and paste the URL below
into your web browser: [{{ $actionUrl }}]({{ $actionUrl }})
@endcomponent
@endisset
{{-- Footer --}}
@slot('footer')
@component('mail::footer')
@isset($companyDetail)
{!! $companyDetail->email_footer !!}
@endisset
@endcomponent
@endslot
@endcomponent