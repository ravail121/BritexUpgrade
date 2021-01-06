@component('mail::message')
    Hello BriteX,

    Please find the list of the customers whose subscription is not closed and have null subscription start date:
    @if($customers)
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Company Name</th>
                <th>Email</th>
            </tr>
            </thead>
            <tbody>
            @foreach($customers as $customer)
                <tr>
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->fname }}</td>
                    <td>{{ $customer->lname }}</td>
                    <td>{{ $customer->company_name }}</td>
                    <td>{{ $customer->email }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endcomponent