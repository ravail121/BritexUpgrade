@component('mail::message')
Hello BriteX,

Please find the list of the customers whose subscription is not closed and have null subscription start date:
@if($customers)
@component('mail::table')
| ID                                    | First Name                        | Last Name                         |  Company Name                         | Email                         |
| ------------------------------------- |:---------------------------------:| :--------------------------------:| :------------------------------------:|:-----------------------------:|
@foreach($customers as $customer)
| {{ $customer['id'] }}                 | {{ $customer['fname'] }}          | {{ $customer['lname'] }}          | {{ $customer['company_name'] }}       | {{ $customer['email'] }}      |
@endforeach
@endcomponent
@endif
@endcomponent