@component('mail::message')
Hello BriteX,

Difference in Invoices:
@if($invoices)
@component('mail::table')
| ID                                    | Sub Total                        | Invoice Item Total                         | 
| ------------------------------------- |:---------------------------------:| :--------------------------------:| 
@foreach($invoices as $invoice)
| {{ $invoice['id'] }}                 | {{ $invoice['subtotal'] }}          | {{ $invoice->sumtotal -  $invoice->sumcoupon  }}          | 
@endforeach
@endcomponent
@endif
@endcomponent