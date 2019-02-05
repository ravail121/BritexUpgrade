<!DOCTYPE html>
<html lang="en">
<head>
  <title>Invoice</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
	<div class="row">
      <div class="col-sm-3">
      	<h2>Logo</h2>
      </div>
      <div class="col-sm-4">
      	<h5>Contact : 1-800-555-1212 &#60;{{ '' }}&#62;</h5>
      </div>
      <div class="col-sm-5">
      	<table class="table table-bordered">
		    <tbody>
		       <tr>
		        <td>Invoice #</td>
		        <td>{{ '' }}</td>
		      </tr>
		      <tr>
		        <td>Period Beginning</td>
		        <td>{{ $invoice['start_date'] }}</td>
		      </tr>
		      <tr>
		        <td>Period Ending</td>
		        <td>{{ $invoice['end_date'] }}</td>
		      </tr>
		      <tr>
		        <td>Due Date</td>
		        <td>{{ $invoice['due_date'] }}</td>
		      </tr>
		      
		    </tbody>
		</table>

      </div>
    </div>
    <div class="row">
    	<p>Your Monthly Bill As Of {{ 'adfd' }} {{ 'fff' }}, {{ 'sdd' }}			
			<br><strong>Important Information </strong>
			<br>1. You are &#60;not&#62; enrolled in autopay. Amount will &#60;not&#62; be forwarded for automatic processing.				
			<br>2. Pay online {{ 'sd' }}		
		</p> 
   	</div>

   	<div class="row">
   		<div class="col-sm-4">
   			<h4>Last Bill</h4>
   			<table class="table table-bordered">
		    <tbody>
		       <tr>
		        <td>Previous Balance</td>
		        <td>$106.46</td>
		      </tr>
		      <tr>
		        <td>Payment(s) Recieved </td>
		        <td>-$106.46</td>
		      </tr>
		      <tr>
		        <td>Thank you</td>
		        <td></td>
		      </tr>
		      <tr>
		        <td>Balance Forward</td>
		        <td>$0.00</td>
		      </tr>
		      
		    </tbody>
		</table>
   		</div>
   		
   		<div class="col-sm-4">
   			<h4>Current Bill</h4>
   			<table class="table table-bordered">
			    <tbody>
			       <tr>
			        <td>Services, Usage & Charges</td>
			        <td>$90.00</td>
			      </tr>
			      <tr>
			        <td>Fees/Taxes </td>
			        <td>$16.46</td>
			      </tr>
			      <tr>
			        <td>Credits</td>
			        <td>$0.00</td>
			      </tr>
			      <tr>
			        <td>Total Charges This Bill</td>
			        <td>${{ $invoice['subtotal'] }}</td>
			      </tr>
			      
			    </tbody>
			</table>
   		</div>

   		<div class="col-sm-4">
   			<h4>Total Amount Due</h4>
   			<table class="table table-bordered">
			    <tbody>
			       <tr>
			        <td></td>
			        <td>Payments</td>
			      </tr>
			      <tr>
			        <td>${{ $invoice['total_due'] }} </td>
			        <td>Due {{ $invoice['due_date'] }}</td>
			      </tr>
			      <tr>
			        <td>Let's Talk! Call us anytime 	</td>
			        <td></td>
			      </tr>
			      <tr>
			        <td>Reseller Phone Number	</td>
			        <td></td>
			      </tr>
			      
			    </tbody>
			</table>
   		</div>


   	</div>

   	<div class="row">

   		<h3>Account Summary</h3>

   		<table class="table table-bordered">
		    <thead>
		      <tr>
		        <th>Phone Number</th>
		        <th>Plan Charges</th>
		        <th>One Time Charges</th>
		        <th>Usage Charges</th>
		        <th>Taxes/Surcharges</th>
		        <th>Credits</th>
		        <th>Total Current Charges</th>
		      </tr>
		    </thead>
		    <tbody>

		    {{-- 	@foreach ($invoice['items'] as $item)
				   <tr>
			        <td>{{$item['item']->subscription->phone_number}}</td>
			        <td>$ {{$item['item']->amount}}</td>
			        <td></td>
			        <td></td>
			        <td>$ {{$item['item']->taxes}}</td>
			        <td></td>
			        <td>@php
			        	echo '$'. collect($item['item']->amount, $item['item']->taxes)->sum();
			        	@endphp
			        </td>
			      </tr>
				@endforeach --}}

		      

		      <tr>
		        <th>Total</th>
		        <th></th>
		        <th></th>
		        <th></th>
		        <th></th>
		        <th></th>
		        <th></th>
		      </tr>
		      
		    </tbody>
		  </table>


   	</div>


             
  
</div>

</body>
</html>
