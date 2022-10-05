@component('mail::message')
Hello BriteX,

Please find the list of the cron with their status:
@if($cronEntries)
@component('mail::table')
| Name                                    | Status                                               |
| ------------------------------------- |:---------------------------------:|
@foreach($cronEntries as $name => $cronEntry)
| {{ $name }}                 | {{ $cronEntry ? 'Ran' : "Didn't run" }}          |
@endforeach
@endcomponent
@endif
@endcomponent